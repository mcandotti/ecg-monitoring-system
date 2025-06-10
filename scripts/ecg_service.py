#!/usr/bin/env python3
"""
Service ECG Flask
API REST pour contrôler les captures ECG
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
import os
import logging
import threading
import time
from datetime import datetime
from process_manager import ECGProcessManager
from database_manager import DatabaseManager

# Configuration logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Initialisation Flask
app = Flask(__name__)
CORS(app)

# Gestionnaire de processus ECG
process_manager = ECGProcessManager()
db_manager = DatabaseManager()

@app.route('/health', methods=['GET'])
def health_check():
    """Point de santé du service"""
    return jsonify({
        'status': 'healthy',
        'timestamp': datetime.now().isoformat(),
        'service': 'ECG Capture Service'
    })

@app.route('/capture/start/<int:diagnostic_id>', methods=['POST'])
def start_capture(diagnostic_id):
    """Démarrer la capture ECG pour un diagnostic"""
    try:
        # Vérifier si une capture est déjà en cours
        if process_manager.is_running(diagnostic_id):
            return jsonify({
                'error': 'Capture already running for this diagnostic',
                'diagnostic_id': diagnostic_id
            }), 400
        
        # Démarrer la capture
        success = process_manager.start_capture(diagnostic_id)
        
        if success:
            # Mettre à jour le statut en base
            db_manager.update_capture_status(diagnostic_id, 'running')
            
            return jsonify({
                'message': 'Capture started successfully',
                'diagnostic_id': diagnostic_id,
                'status': 'running'
            })
        else:
            return jsonify({
                'error': 'Failed to start capture',
                'diagnostic_id': diagnostic_id
            }), 500
            
    except Exception as e:
        logger.error(f"Error starting capture: {e}")
        return jsonify({
            'error': str(e),
            'diagnostic_id': diagnostic_id
        }), 500

@app.route('/capture/stop/<int:diagnostic_id>', methods=['POST'])
def stop_capture(diagnostic_id):
    """Arrêter la capture ECG pour un diagnostic"""
    try:
        # Arrêter la capture
        success = process_manager.stop_capture(diagnostic_id)
        
        if success:
            # Mettre à jour le statut en base
            db_manager.update_capture_status(diagnostic_id, 'stopped')
            
            return jsonify({
                'message': 'Capture stopped successfully',
                'diagnostic_id': diagnostic_id,
                'status': 'stopped'
            })
        else:
            return jsonify({
                'error': 'Failed to stop capture or no capture running',
                'diagnostic_id': diagnostic_id
            }), 400
            
    except Exception as e:
        logger.error(f"Error stopping capture: {e}")
        return jsonify({
            'error': str(e),
            'diagnostic_id': diagnostic_id
        }), 500

@app.route('/capture/status/<int:diagnostic_id>', methods=['GET'])
def get_capture_status(diagnostic_id):
    """Obtenir le statut de capture d'un diagnostic"""
    try:
        # Vérifier le statut du processus
        is_running = process_manager.is_running(diagnostic_id)
        
        # Récupérer les informations de la base
        session_info = db_manager.get_capture_session(diagnostic_id)
        
        return jsonify({
            'diagnostic_id': diagnostic_id,
            'is_running': is_running,
            'session_info': session_info,
            'timestamp': datetime.now().isoformat()
        })
        
    except Exception as e:
        logger.error(f"Error getting capture status: {e}")
        return jsonify({
            'error': str(e),
            'diagnostic_id': diagnostic_id
        }), 500

@app.route('/images/<int:diagnostic_id>', methods=['GET'])
def get_diagnostic_images(diagnostic_id):
    """Récupérer toutes les images d'un diagnostic"""
    try:
        images = db_manager.get_diagnostic_images(diagnostic_id)
        
        return jsonify({
            'diagnostic_id': diagnostic_id,
            'total_images': len(images),
            'images': images
        })
        
    except Exception as e:
        logger.error(f"Error getting diagnostic images: {e}")
        return jsonify({
            'error': str(e),
            'diagnostic_id': diagnostic_id
        }), 500

@app.route('/image/<int:image_id>', methods=['GET'])
def get_image(image_id):
    """Récupérer une image spécifique"""
    try:
        image_data = db_manager.get_image_blob(image_id)
        
        if image_data:
            return jsonify({
                'image_id': image_id,
                'image_data': image_data['image_blob'],
                'created_at': image_data['image_created_at']
            })
        else:
            return jsonify({
                'error': 'Image not found',
                'image_id': image_id
            }), 404
            
    except Exception as e:
        logger.error(f"Error getting image: {e}")
        return jsonify({
            'error': str(e),
            'image_id': image_id
        }), 500

@app.route('/capture/cleanup', methods=['POST'])
def cleanup_processes():
    """Nettoyer tous les processus de capture"""
    try:
        cleaned = process_manager.cleanup_all()
        
        return jsonify({
            'message': 'Cleanup completed',
            'processes_cleaned': cleaned
        })
        
    except Exception as e:
        logger.error(f"Error during cleanup: {e}")
        return jsonify({
            'error': str(e)
        }), 500

@app.errorhandler(404)
def not_found(error):
    return jsonify({'error': 'Endpoint not found'}), 404

@app.errorhandler(500)
def internal_error(error):
    return jsonify({'error': 'Internal server error'}), 500

if __name__ == '__main__':
    logger.info("Starting ECG Capture Service...")
    
    # Nettoyer les processus au démarrage
    process_manager.cleanup_all()
    
    # Démarrer le serveur Flask
    app.run(
        host='0.0.0.0',
        port=5000,
        debug=False,
        threaded=True
    ) 