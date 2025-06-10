import RPi.GPIO as GPIO
import spidev
import matplotlib.pyplot as plt
import numpy as np
from collections import deque
import io
import mariadb
import time

# === CONFIGURATION ===
SPI_BUS = 0
SPI_DEVICE = 0
CS_GPIO_PIN = 4

# === INITIALISATION GPIO & SPI ===
GPIO.setmode(GPIO.BCM)
GPIO.setup(4, GPIO.OUT)
GPIO.output(4, GPIO.HIGH)

spi = spidev.SpiDev()
spi.open(0, 0)
spi.max_speed_hz = 1000000

# === FONCTION DE LECTURE SPI ===
def analog_read():
    r = spi.readbytes(2)
    adc_out = (((r[0] & 0x1F) << 8) + (r[1] & 0xFE)) >> 3
    return adc_out

# === PRÉPARATION AFFICHAGE TEMPS RÉEL ===
plt.ion()
fig, ax = plt.subplots()
x = np.arange(100)
y = [0] * 100
line, = ax.plot(x, y)
plt.ylim([0, 3.3])
plt.xlabel("Échantillons")
plt.ylabel("Tension ECG (V)")
plt.title("ECG en temps réel")

voltage_series = deque([0]*100, maxlen=100)

# === GÉNÉRER ET SAUVEGARDER GRAPHIQUE ===
def generate_plot(data):
    fig_save, ax_save = plt.subplots()
    ax_save.plot(data)
    ax_save.set_title("Capture ECG")
    ax_save.set_xlabel("Échantillons")
    ax_save.set_ylabel("Tension (V)")
    ax_save.set_ylim([0, 3.3])

    buf = io.BytesIO()
    fig_save.savefig(buf, format='png')
    plt.close(fig_save)
    buf.seek(0)
    return buf.getvalue()
    

# === SAUVEGARDE DANS LA BASE DE DONNÉES ===
def save_plot_to_db(img_data):
    try:
        conn = mariadb.connect(
            user='ecg',
            password='Password13',
            host='localhost',
            database='dbecg'
        )
        print('connexion etablie')
        cursor = conn.cursor()
        cursor.execute(f"INSERT INTO ECG (ECG, ID_P) VALUES (?, ?)", (img_data, 1))
        conn.commit()
        print("✅ Graphique ECG sauvegardé dans la base de données.")
    except mariadb.Error as e:
        print(f"❌ Erreur de base de données : {e}")
    finally:img
    conn.close()

# === BOUCLE PRINCIPALE ===
counter = 0
try:
    while True:
        raw = analog_read()
        voltage = raw * 3.3 / 1024
        voltage_series.append(voltage)
        line.set_ydata(voltage_series)
        fig.canvas.draw()
        fig.canvas.flush_events()

        time.sleep(0.01)  # 100 Hz ~ 10 ms

        counter += 1
        if counter == 50:  # toutes les 500 mesures (~5 sec)
            img = generate_plot(list(voltage_series))
            save_plot_to_db(img)
            counter = 0
            """ print(img) """

except KeyboardInterrupt:
    print("Arrêt par l'utilisateur.")
finally:
    spi.close()
    GPIO.cleanup()