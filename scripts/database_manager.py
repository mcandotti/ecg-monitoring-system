#!/usr/bin/env python3
"""
Gestionnaire de base de données pour ECG
Gère toutes les opérations de base de données liées aux captures ECG
"""

import pymysql
import logging
import os
import base64
from datetime import datetime
from typing import List, Dict, Optional, Any

logger = logging.getLogger(__name__)

class DatabaseManager:
    """Gestionnaire de base de données pour ECG"""
    
    def __init__(self):
        """Initialiser la connexion à la base de données"""
        self.connection_params = {
            'host': os.getenv('DB_HOST', 'mysql'),
            'port': int(os.getenv('DB_PORT', 3306)),
            'user': os.getenv('DB_USER', 'ecg_user'),
            'password': os.getenv('DB_PASSWORD', 'secure_password'),
            'database': os.getenv('DB_NAME', 'ecg_database'),
            'charset': 'utf8mb4'
        }
    
    def _get_connection(self):
        """
        Obtenir une connexion à la base de données
        
        Returns:
            pymysql.Connection: Connexion à la base
        """
        try:
            return pymysql.connect(**self.connection_params)
        except Exception as e:
            logger.error(f"Database connection error: {e}")
            raise
    
    def save_ecg_image(self, diagnostic_id: int, image_blob: bytes, capture_duration: int = 5) -> bool:
        """
        Sauvegarder une image ECG en base de données
        
        Args:
            diagnostic_id: ID du diagnostic
            image_blob: Données de l'image en bytes
            capture_duration: Durée de capture en secondes
            
        Returns:
            bool: True si sauvegardé avec succès
        """
        try:
            with self._get_connection() as conn:
                with conn.cursor() as cursor:
                    sql = """
                        INSERT INTO ecg_data 
                        (diagnostic_id, image_blob, capture_duration, status)
                        VALUES (%s, %s, %s, %s)
                    """
                    
                    cursor.execute(sql, (diagnostic_id, image_blob, capture_duration, 'completed'))
                    conn.commit()
                    
                    logger.debug(f"Saved ECG image for diagnostic {diagnostic_id}")
                    return True
                    
        except Exception as e:
            logger.error(f"Error saving ECG image: {e}")
            return False
    
    def get_diagnostic_images(self, diagnostic_id: int) -> List[Dict[str, Any]]:
        """
        Récupérer toutes les images d'un diagnostic
        
        Args:
            diagnostic_id: ID du diagnostic
            
        Returns:
            List[Dict]: Liste des images avec métadonnées
        """
        try:
            with self._get_connection() as conn:
                with conn.cursor(pymysql.cursors.DictCursor) as cursor:
                    sql = """
                        SELECT id, image_created_at, capture_duration, status
                        FROM ecg_data 
                        WHERE diagnostic_id = %s 
                        ORDER BY image_created_at DESC
                    """
                    
                    cursor.execute(sql, (diagnostic_id,))
                    results = cursor.fetchall()
                    
                    # Convertir les timestamps en string
                    for result in results:
                        if result['image_created_at']:
                            result['image_created_at'] = result['image_created_at'].isoformat()
                    
                    return results
                    
        except Exception as e:
            logger.error(f"Error getting diagnostic images: {e}")
            return []
    
    def get_image_blob(self, image_id: int) -> Optional[Dict[str, Any]]:
        """
        Récupérer les données blob d'une image
        
        Args:
            image_id: ID de l'image
            
        Returns:
            Dict: Données de l'image en base64
        """
        try:
            with self._get_connection() as conn:
                with conn.cursor(pymysql.cursors.DictCursor) as cursor:
                    sql = """
                        SELECT image_blob, image_created_at
                        FROM ecg_data 
                        WHERE id = %s
                    """
                    
                    cursor.execute(sql, (image_id,))
                    result = cursor.fetchone()
                    
                    if result:
                        # Encoder l'image en base64 pour transmission
                        result['image_blob'] = base64.b64encode(result['image_blob']).decode('utf-8')
                        result['image_created_at'] = result['image_created_at'].isoformat()
                        return result
                    
                    return None
                    
        except Exception as e:
            logger.error(f"Error getting image blob: {e}")
            return None
    
    def init_capture_session(self, diagnostic_id: int) -> bool:
        """
        Initialiser une session de capture
        
        Args:
            diagnostic_id: ID du diagnostic
            
        Returns:
            bool: True si initialisé avec succès
        """
        try:
            with self._get_connection() as conn:
                with conn.cursor() as cursor:
                    # Vérifier si une session existe déjà
                    check_sql = """
                        SELECT id FROM ecg_capture_sessions 
                        WHERE diagnostic_id = %s
                    """
                    cursor.execute(check_sql, (diagnostic_id,))
                    existing = cursor.fetchone()
                    
                    if existing:
                        # Mettre à jour la session existante
                        update_sql = """
                            UPDATE ecg_capture_sessions 
                            SET status = %s, started_at = %s, stopped_at = NULL, last_error = NULL
                            WHERE diagnostic_id = %s
                        """
                        cursor.execute(update_sql, ('running', datetime.now(), diagnostic_id))
                    else:
                        # Créer une nouvelle session
                        insert_sql = """
                            INSERT INTO ecg_capture_sessions 
                            (diagnostic_id, status, started_at)
                            VALUES (%s, %s, %s)
                        """
                        cursor.execute(insert_sql, (diagnostic_id, 'running', datetime.now()))
                    
                    conn.commit()
                    return True
                    
        except Exception as e:
            logger.error(f"Error initializing capture session: {e}")
            return False
    
    def update_capture_status(self, diagnostic_id: int, status: str, error_message: str = None) -> bool:
        """
        Mettre à jour le statut d'une capture
        
        Args:
            diagnostic_id: ID du diagnostic
            status: Nouveau statut
            error_message: Message d'erreur optionnel
            
        Returns:
            bool: True si mis à jour avec succès
        """
        try:
            with self._get_connection() as conn:
                with conn.cursor() as cursor:
                    sql = """
                        UPDATE ecg_capture_sessions 
                        SET status = %s, last_error = %s
                        WHERE diagnostic_id = %s
                    """
                    
                    if status == 'stopped':
                        sql = """
                            UPDATE ecg_capture_sessions 
                            SET status = %s, stopped_at = %s, last_error = %s
                            WHERE diagnostic_id = %s
                        """
                        cursor.execute(sql, (status, datetime.now(), error_message, diagnostic_id))
                    else:
                        cursor.execute(sql, (status, error_message, diagnostic_id))
                    
                    conn.commit()
                    return True
                    
        except Exception as e:
            logger.error(f"Error updating capture status: {e}")
            return False
    
    def update_capture_session_count(self, diagnostic_id: int, total_images: int) -> bool:
        """
        Mettre à jour le compteur d'images d'une session
        
        Args:
            diagnostic_id: ID du diagnostic
            total_images: Nombre total d'images
            
        Returns:
            bool: True si mis à jour avec succès
        """
        try:
            with self._get_connection() as conn:
                with conn.cursor() as cursor:
                    sql = """
                        UPDATE ecg_capture_sessions 
                        SET total_images = %s
                        WHERE diagnostic_id = %s
                    """
                    
                    cursor.execute(sql, (total_images, diagnostic_id))
                    conn.commit()
                    return True
                    
        except Exception as e:
            logger.error(f"Error updating capture session count: {e}")
            return False
    
    def get_capture_session(self, diagnostic_id: int) -> Optional[Dict[str, Any]]:
        """
        Récupérer les informations d'une session de capture
        
        Args:
            diagnostic_id: ID du diagnostic
            
        Returns:
            Dict: Informations de la session
        """
        try:
            with self._get_connection() as conn:
                with conn.cursor(pymysql.cursors.DictCursor) as cursor:
                    sql = """
                        SELECT * FROM ecg_capture_sessions 
                        WHERE diagnostic_id = %s
                    """
                    
                    cursor.execute(sql, (diagnostic_id,))
                    result = cursor.fetchone()
                    
                    if result:
                        # Convertir les timestamps
                        for field in ['started_at', 'stopped_at']:
                            if result[field]:
                                result[field] = result[field].isoformat()
                    
                    return result
                    
        except Exception as e:
            logger.error(f"Error getting capture session: {e}")
            return None
    
    def finalize_capture_session(self, diagnostic_id: int, final_count: int) -> bool:
        """
        Finaliser une session de capture
        
        Args:
            diagnostic_id: ID du diagnostic
            final_count: Nombre final d'images
            
        Returns:
            bool: True si finalisé avec succès
        """
        try:
            with self._get_connection() as conn:
                with conn.cursor() as cursor:
                    sql = """
                        UPDATE ecg_capture_sessions 
                        SET status = %s, stopped_at = %s, total_images = %s
                        WHERE diagnostic_id = %s
                    """
                    
                    cursor.execute(sql, ('stopped', datetime.now(), final_count, diagnostic_id))
                    conn.commit()
                    return True
                    
        except Exception as e:
            logger.error(f"Error finalizing capture session: {e}")
            return False
    
    def get_latest_images(self, diagnostic_id: int, limit: int = 5) -> List[Dict[str, Any]]:
        """
        Récupérer les dernières images d'un diagnostic
        
        Args:
            diagnostic_id: ID du diagnostic
            limit: Nombre maximum d'images à retourner
            
        Returns:
            List[Dict]: Liste des dernières images
        """
        try:
            with self._get_connection() as conn:
                with conn.cursor(pymysql.cursors.DictCursor) as cursor:
                    sql = """
                        SELECT id, image_created_at, capture_duration, status
                        FROM ecg_data 
                        WHERE diagnostic_id = %s 
                        ORDER BY image_created_at DESC
                        LIMIT %s
                    """
                    
                    cursor.execute(sql, (diagnostic_id, limit))
                    results = cursor.fetchall()
                    
                    # Convertir les timestamps
                    for result in results:
                        if result['image_created_at']:
                            result['image_created_at'] = result['image_created_at'].isoformat()
                    
                    return results
                    
        except Exception as e:
            logger.error(f"Error getting latest images: {e}")
            return [] 