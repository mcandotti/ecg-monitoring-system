#!/usr/bin/env python3
"""
Gestionnaire de processus ECG
Gère le démarrage, l'arrêt et le suivi des processus de capture
"""

import multiprocessing
import threading
import time
import logging
import signal
import os
from typing import Dict, Optional
from ecg_capture import ECGCapture

logger = logging.getLogger(__name__)

class ECGProcessManager:
    """Gestionnaire des processus de capture ECG"""
    
    def __init__(self):
        self.processes: Dict[int, multiprocessing.Process] = {}
        self.stop_events: Dict[int, multiprocessing.Event] = {}
        self.lock = threading.Lock()
        
    def start_capture(self, diagnostic_id: int) -> bool:
        """
        Démarrer une capture ECG pour un diagnostic
        
        Args:
            diagnostic_id: ID du diagnostic
            
        Returns:
            bool: True si démarré avec succès
        """
        with self.lock:
            try:
                # Vérifier si déjà en cours
                if diagnostic_id in self.processes:
                    if self.processes[diagnostic_id].is_alive():
                        logger.warning(f"Capture already running for diagnostic {diagnostic_id}")
                        return False
                    else:
                        # Nettoyer le processus mort
                        self._cleanup_process(diagnostic_id)
                
                # Créer un événement d'arrêt
                stop_event = multiprocessing.Event()
                self.stop_events[diagnostic_id] = stop_event
                
                # Créer et démarrer le processus
                process = multiprocessing.Process(
                    target=self._run_capture,
                    args=(diagnostic_id, stop_event)
                )
                
                process.start()
                self.processes[diagnostic_id] = process
                
                logger.info(f"Started capture process for diagnostic {diagnostic_id} (PID: {process.pid})")
                return True
                
            except Exception as e:
                logger.error(f"Failed to start capture for diagnostic {diagnostic_id}: {e}")
                return False
    
    def stop_capture(self, diagnostic_id: int) -> bool:
        """
        Arrêter une capture ECG
        
        Args:
            diagnostic_id: ID du diagnostic
            
        Returns:
            bool: True si arrêté avec succès
        """
        with self.lock:
            try:
                if diagnostic_id not in self.processes:
                    logger.warning(f"No capture process found for diagnostic {diagnostic_id}")
                    return False
                
                process = self.processes[diagnostic_id]
                
                if not process.is_alive():
                    logger.warning(f"Process for diagnostic {diagnostic_id} is already dead")
                    self._cleanup_process(diagnostic_id)
                    return True
                
                # Signaler l'arrêt
                if diagnostic_id in self.stop_events:
                    self.stop_events[diagnostic_id].set()
                
                # Attendre l'arrêt gracieux
                process.join(timeout=5)
                
                if process.is_alive():
                    # Forcer l'arrêt si nécessaire
                    logger.warning(f"Forcing termination of process for diagnostic {diagnostic_id}")
                    process.terminate()
                    process.join(timeout=2)
                    
                    if process.is_alive():
                        # Dernier recours
                        logger.error(f"Killing process for diagnostic {diagnostic_id}")
                        process.kill()
                        process.join()
                
                self._cleanup_process(diagnostic_id)
                logger.info(f"Stopped capture process for diagnostic {diagnostic_id}")
                return True
                
            except Exception as e:
                logger.error(f"Failed to stop capture for diagnostic {diagnostic_id}: {e}")
                return False
    
    def is_running(self, diagnostic_id: int) -> bool:
        """
        Vérifier si une capture est en cours
        
        Args:
            diagnostic_id: ID du diagnostic
            
        Returns:
            bool: True si en cours
        """
        with self.lock:
            if diagnostic_id not in self.processes:
                return False
            
            process = self.processes[diagnostic_id]
            is_alive = process.is_alive()
            
            # Nettoyer si le processus est mort
            if not is_alive:
                self._cleanup_process(diagnostic_id)
            
            return is_alive
    
    def get_running_processes(self) -> Dict[int, dict]:
        """
        Récupérer la liste des processus en cours
        
        Returns:
            dict: Dictionnaire des processus en cours
        """
        with self.lock:
            running = {}
            
            for diagnostic_id, process in list(self.processes.items()):
                if process.is_alive():
                    running[diagnostic_id] = {
                        'pid': process.pid,
                        'started_at': getattr(process, '_started_at', None)
                    }
                else:
                    # Nettoyer les processus morts
                    self._cleanup_process(diagnostic_id)
            
            return running
    
    def cleanup_all(self) -> int:
        """
        Nettoyer tous les processus
        
        Returns:
            int: Nombre de processus nettoyés
        """
        with self.lock:
            cleaned_count = 0
            
            for diagnostic_id in list(self.processes.keys()):
                if self.stop_capture(diagnostic_id):
                    cleaned_count += 1
            
            logger.info(f"Cleaned up {cleaned_count} processes")
            return cleaned_count
    
    def _cleanup_process(self, diagnostic_id: int):
        """Nettoyer les ressources d'un processus"""
        if diagnostic_id in self.processes:
            del self.processes[diagnostic_id]
        
        if diagnostic_id in self.stop_events:
            del self.stop_events[diagnostic_id]
    
    def _run_capture(self, diagnostic_id: int, stop_event: multiprocessing.Event):
        """
        Fonction exécutée dans le processus de capture
        
        Args:
            diagnostic_id: ID du diagnostic
            stop_event: Événement d'arrêt
        """
        try:
            logger.info(f"Starting ECG capture process for diagnostic {diagnostic_id}")
            
            # Créer l'instance de capture
            ecg_capture = ECGCapture(diagnostic_id, stop_event)
            
            # Démarrer la capture
            ecg_capture.run()
            
            logger.info(f"ECG capture process completed for diagnostic {diagnostic_id}")
            
        except Exception as e:
            logger.error(f"Error in capture process for diagnostic {diagnostic_id}: {e}")
        finally:
            # Nettoyer les ressources GPIO
            try:
                import RPi.GPIO as GPIO
                GPIO.cleanup()
            except Exception:
                pass  # Ignorer si GPIO n'est pas disponible
    
    def __del__(self):
        """Nettoyage lors de la destruction"""
        try:
            self.cleanup_all()
        except Exception:
            pass 