#!/usr/bin/env python3
"""
Classe de capture ECG
Version adaptée pour la nouvelle structure de base de données
"""

import RPi.GPIO as GPIO
import spidev
import matplotlib.pyplot as plt
import numpy as np
from collections import deque
import io
import time
import logging
import multiprocessing
from datetime import datetime
from database_manager import DatabaseManager

logger = logging.getLogger(__name__)

class ECGCapture:
    """Classe pour gérer la capture ECG"""
    
    # Configuration par défaut
    SPI_BUS = 0
    SPI_DEVICE = 0
    CS_GPIO_PIN = 4
    SAMPLE_RATE = 100  # Hz
    SAVE_INTERVAL = 5  # secondes
    BUFFER_SIZE = 500  # échantillons
    
    def __init__(self, diagnostic_id: int, stop_event: multiprocessing.Event):
        """
        Initialiser la capture ECG
        
        Args:
            diagnostic_id: ID du diagnostic
            stop_event: Événement pour arrêter la capture
        """
        self.diagnostic_id = diagnostic_id
        self.stop_event = stop_event
        self.db_manager = DatabaseManager()
        
        # Buffers pour les données
        self.voltage_buffer = deque(maxlen=self.BUFFER_SIZE)
        self.time_buffer = deque(maxlen=self.BUFFER_SIZE)
        
        # Configuration GPIO et SPI
        self.spi = None
        self._setup_hardware()
        
        # Compteurs
        self.sample_count = 0
        self.save_count = 0
        self.start_time = time.time()
        
    def _setup_hardware(self):
        """Configurer le matériel GPIO et SPI"""
        try:
            # Configuration GPIO
            GPIO.setmode(GPIO.BCM)
            GPIO.setup(self.CS_GPIO_PIN, GPIO.OUT)
            GPIO.output(self.CS_GPIO_PIN, GPIO.HIGH)
            
            # Configuration SPI
            self.spi = spidev.SpiDev()
            self.spi.open(self.SPI_BUS, self.SPI_DEVICE)
            self.spi.max_speed_hz = 1000000
            
            logger.info(f"Hardware setup completed for diagnostic {self.diagnostic_id}")
            
        except Exception as e:
            logger.error(f"Failed to setup hardware: {e}")
            raise
    
    def _analog_read(self) -> int:
        """
        Lire une valeur analogique via SPI
        
        Returns:
            int: Valeur ADC (0-1023)
        """
        try:
            r = self.spi.readbytes(2)
            adc_out = (((r[0] & 0x1F) << 8) + (r[1] & 0xFE)) >> 3
            return adc_out
        except Exception as e:
            logger.error(f"Error reading analog value: {e}")
            return 0
    
    def _convert_to_voltage(self, adc_value: int) -> float:
        """
        Convertir la valeur ADC en tension
        
        Args:
            adc_value: Valeur ADC (0-1023)
            
        Returns:
            float: Tension en volts
        """
        return adc_value * 3.3 / 1024
    
    def _generate_plot(self, voltage_data: list, time_data: list) -> bytes:
        """
        Générer un graphique ECG
        
        Args:
            voltage_data: Données de tension
            time_data: Données temporelles
            
        Returns:
            bytes: Image PNG en bytes
        """
        try:
            # Créer le graphique
            fig, ax = plt.subplots(figsize=(12, 6))
            ax.plot(time_data, voltage_data, 'b-', linewidth=1)
            
            # Configuration du graphique
            ax.set_title(f'ECG - Diagnostic #{self.diagnostic_id} - {datetime.now().strftime("%H:%M:%S")}')
            ax.set_xlabel('Temps (s)')
            ax.set_ylabel('Tension ECG (V)')
            ax.set_ylim([0, 3.3])
            ax.grid(True, alpha=0.3)
            
            # Sauvegarder en buffer
            buffer = io.BytesIO()
            fig.savefig(buffer, format='png', dpi=100, bbox_inches='tight')
            plt.close(fig)
            
            buffer.seek(0)
            return buffer.getvalue()
            
        except Exception as e:
            logger.error(f"Error generating plot: {e}")
            return b''
    
    def _save_to_database(self, image_data: bytes):
        """
        Sauvegarder l'image en base de données
        
        Args:
            image_data: Données de l'image PNG
        """
        try:
            if image_data:
                success = self.db_manager.save_ecg_image(
                    diagnostic_id=self.diagnostic_id,
                    image_blob=image_data,
                    capture_duration=self.SAVE_INTERVAL
                )
                
                if success:
                    self.save_count += 1
                    logger.info(f"Saved ECG image {self.save_count} for diagnostic {self.diagnostic_id}")
                else:
                    logger.error(f"Failed to save ECG image for diagnostic {self.diagnostic_id}")
            
        except Exception as e:
            logger.error(f"Error saving to database: {e}")
    
    def run(self):
        """
        Boucle principale de capture
        """
        logger.info(f"Starting ECG capture for diagnostic {self.diagnostic_id}")
        
        try:
            # Initialiser la session de capture
            self.db_manager.init_capture_session(self.diagnostic_id)
            
            last_save_time = time.time()
            
            while not self.stop_event.is_set():
                try:
                    # Lire une valeur
                    raw_value = self._analog_read()
                    voltage = self._convert_to_voltage(raw_value)
                    current_time = time.time() - self.start_time
                    
                    # Ajouter aux buffers
                    self.voltage_buffer.append(voltage)
                    self.time_buffer.append(current_time)
                    
                    self.sample_count += 1
                    
                    # Vérifier s'il faut sauvegarder
                    if time.time() - last_save_time >= self.SAVE_INTERVAL:
                        # Créer et sauvegarder le graphique
                        if len(self.voltage_buffer) > 0:
                            image_data = self._generate_plot(
                                list(self.voltage_buffer),
                                list(self.time_buffer)
                            )
                            self._save_to_database(image_data)
                        
                        last_save_time = time.time()
                        
                        # Mettre à jour le compteur d'images
                        self.db_manager.update_capture_session_count(
                            self.diagnostic_id, 
                            self.save_count
                        )
                    
                    # Respecter la fréquence d'échantillonnage
                    time.sleep(1.0 / self.SAMPLE_RATE)
                    
                except Exception as e:
                    logger.error(f"Error in capture loop: {e}")
                    time.sleep(0.1)  # Pause courte avant de continuer
            
            logger.info(f"ECG capture stopped for diagnostic {self.diagnostic_id}")
            logger.info(f"Total samples: {self.sample_count}, Images saved: {self.save_count}")
            
        except Exception as e:
            logger.error(f"Critical error in ECG capture: {e}")
            self.db_manager.update_capture_status(self.diagnostic_id, 'error', str(e))
            
        finally:
            self._cleanup()
    
    def _cleanup(self):
        """Nettoyer les ressources"""
        try:
            if self.spi:
                self.spi.close()
            
            GPIO.cleanup()
            
            # Finaliser la session de capture
            self.db_manager.finalize_capture_session(self.diagnostic_id, self.save_count)
            
            logger.info(f"Cleanup completed for diagnostic {self.diagnostic_id}")
            
        except Exception as e:
            logger.error(f"Error during cleanup: {e}")
    
    def __del__(self):
        """Nettoyage lors de la destruction"""
        try:
            self._cleanup()
        except Exception:
            pass 