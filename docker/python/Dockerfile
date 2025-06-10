FROM python:3.9-slim

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    gcc \
    g++ \
    libc6-dev \
    pkg-config \
    libfreetype6-dev \
    libpng-dev \
    python3-dev \
    python3-pip \
    && rm -rf /var/lib/apt/lists/*

# Création du répertoire de travail
WORKDIR /app

# Copie du fichier requirements
COPY requirements.txt .

# Installation des dépendances Python
RUN pip install --no-cache-dir -r requirements.txt

# Copie des scripts
COPY . .

# Port d'exposition pour l'API Flask
EXPOSE 5000

# Variables d'environnement
ENV PYTHONPATH=/app
ENV FLASK_APP=ecg_service.py
ENV FLASK_ENV=production

# Commande par défaut
CMD ["python", "ecg_service.py"] 