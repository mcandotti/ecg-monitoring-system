/**
 * Contrôleur ECG en temps réel
 * Gère les captures ECG, la galerie d'images et les mises à jour temps réel
 */

class ECGRealtimeController {
    constructor(config) {
        this.config = config;
        this.isCapturing = false;
        this.captureStartTime = null;
        this.refreshInterval = null;
        this.imageCache = new Map();
        this.retryCount = 0;
        this.maxRetries = config.maxRetries || 3;
        
        // Éléments DOM
        this.elements = {};
        
        // Bind des méthodes
        this.handleStartCapture = this.handleStartCapture.bind(this);
        this.handleStopCapture = this.handleStopCapture.bind(this);
        this.refreshGallery = this.refreshGallery.bind(this);
        this.checkCaptureStatus = this.checkCaptureStatus.bind(this);
    }
    
    /**
     * Initialiser le contrôleur
     */
    init() {
        this.initializeElements();
        this.attachEventListeners();
        this.loadInitialData();
        this.startStatusPolling();
        
        console.log('ECG Realtime Controller initialized');
    }
    
    /**
     * Initialiser les références aux éléments DOM
     */
    initializeElements() {
        this.elements = {
            startBtn: document.getElementById('startCaptureBtn'),
            stopBtn: document.getElementById('stopCaptureBtn'),
            status: document.getElementById('captureStatus'),
            statusLight: document.querySelector('.status-light'),
            statusText: document.querySelector('.status-text'),
            imageCount: document.getElementById('imageCount'),
            captureTime: document.getElementById('captureTime'),
            loadingGallery: document.getElementById('loadingGallery'),
            galleryContainer: document.getElementById('galleryContainer'),
            imageGallery: document.getElementById('imageGallery'),
            noImagesMessage: document.getElementById('noImagesMessage'),
            refreshBtn: document.getElementById('refreshGalleryBtn'),
            gridViewBtn: document.getElementById('gridViewBtn'),
            listViewBtn: document.getElementById('listViewBtn'),
            imageModal: new bootstrap.Modal(document.getElementById('imageModal')),
            modalImage: document.getElementById('modalImage'),
            imageInfo: document.getElementById('imageInfo'),
            downloadBtn: document.getElementById('downloadImageBtn')
        };
    }
    
    /**
     * Attacher les écouteurs d'événements
     */
    attachEventListeners() {
        // Boutons de contrôle
        this.elements.startBtn.addEventListener('click', this.handleStartCapture);
        this.elements.stopBtn.addEventListener('click', this.handleStopCapture);
        
        // Galerie
        this.elements.refreshBtn.addEventListener('click', this.refreshGallery);
        this.elements.gridViewBtn.addEventListener('click', () => this.switchView('grid'));
        this.elements.listViewBtn.addEventListener('click', () => this.switchView('list'));
        
        // Modal
        this.elements.downloadBtn.addEventListener('click', this.downloadCurrentImage.bind(this));
        
        // Gestion de la visibilité de la page
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pausePolling();
            } else {
                this.resumePolling();
            }
        });
    }
    
    /**
     * Charger les données initiales
     */
    async loadInitialData() {
        await this.checkCaptureStatus();
        await this.refreshGallery();
    }
    
    /**
     * Démarrer la capture ECG
     */
    async handleStartCapture() {
        try {
            this.setButtonsLoading(true);
            this.showNotification('Démarrage de la capture...', 'info');
            
            const response = await this.apiRequest(`/start/${this.config.diagnosticId}`, 'POST');
            
            if (response.success) {
                this.isCapturing = true;
                this.captureStartTime = Date.now();
                this.updateUI();
                this.showNotification('Capture ECG démarrée avec succès', 'success');
                this.startCaptureTimer();
            } else {
                throw new Error(response.error || 'Erreur lors du démarrage');
            }
            
        } catch (error) {
            console.error('Start capture error:', error);
            this.showNotification('Erreur: ' + error.message, 'error');
        } finally {
            this.setButtonsLoading(false);
        }
    }
    
    /**
     * Arrêter la capture ECG
     */
    async handleStopCapture() {
        try {
            this.setButtonsLoading(true);
            this.showNotification('Arrêt de la capture...', 'info');
            
            const response = await this.apiRequest(`/stop/${this.config.diagnosticId}`, 'POST');
            
            if (response.success) {
                this.isCapturing = false;
                this.captureStartTime = null;
                this.updateUI();
                this.showNotification('Capture ECG arrêtée', 'success');
                this.stopCaptureTimer();
                await this.refreshGallery();
            } else {
                throw new Error(response.error || 'Erreur lors de l\'arrêt');
            }
            
        } catch (error) {
            console.error('Stop capture error:', error);
            this.showNotification('Erreur: ' + error.message, 'error');
        } finally {
            this.setButtonsLoading(false);
        }
    }
    
    /**
     * Vérifier le statut de la capture
     */
    async checkCaptureStatus() {
        try {
            const response = await this.apiRequest(`/status/${this.config.diagnosticId}`, 'GET');
            
            if (response.success) {
                const data = response.data;
                this.isCapturing = data.is_running;
                
                if (data.session_info) {
                    this.updateSessionInfo(data.session_info);
                }
                
                this.updateUI();
                this.retryCount = 0; // Reset retry count on success
            }
            
        } catch (error) {
            console.error('Status check error:', error);
            this.retryCount++;
            
            if (this.retryCount >= this.maxRetries) {
                this.showNotification('Connexion au service ECG perdue', 'error');
                this.pausePolling();
            }
        }
    }
    
    /**
     * Actualiser la galerie d'images
     */
    async refreshGallery() {
        try {
            this.elements.loadingGallery.style.display = 'block';
            this.elements.galleryContainer.style.display = 'none';
            
            const response = await this.apiRequest(`/images/${this.config.diagnosticId}`, 'GET');
            
            if (response.success) {
                const images = response.data.images || [];
                this.renderGallery(images);
                this.updateImageCount(images.length);
            }
            
        } catch (error) {
            console.error('Gallery refresh error:', error);
            this.showNotification('Erreur lors du chargement des images', 'error');
        } finally {
            this.elements.loadingGallery.style.display = 'none';
            this.elements.galleryContainer.style.display = 'block';
        }
    }
    
    /**
     * Rendre la galerie d'images
     */
    renderGallery(images) {
        const gallery = this.elements.imageGallery;
        gallery.innerHTML = '';
        
        if (images.length === 0) {
            this.elements.noImagesMessage.style.display = 'block';
            return;
        }
        
        this.elements.noImagesMessage.style.display = 'none';
        
        images.forEach((image, index) => {
            const imageElement = this.createImageElement(image, index);
            gallery.appendChild(imageElement);
        });
    }
    
    /**
     * Créer un élément d'image pour la galerie
     */
    createImageElement(image, index) {
        const div = document.createElement('div');
        div.className = 'image-item';
        div.setAttribute('data-image-id', image.id);
        
        const createdAt = new Date(image.image_created_at);
        const timeStr = createdAt.toLocaleTimeString();
        
        div.innerHTML = `
            <div class="image-placeholder">
                <div class="image-loading">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                </div>
                <img class="image-thumbnail" src="" alt="ECG ${index + 1}" style="display: none;">
            </div>
            <div class="image-info">
                <div class="image-title">ECG #${index + 1}</div>
                <div class="image-time">${timeStr}</div>
                <div class="image-actions">
                    <button class="btn btn-sm btn-primary view-btn">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-success download-btn">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </div>
        `;
        
        // Événements
        div.querySelector('.view-btn').addEventListener('click', () => this.viewImage(image.id));
        div.querySelector('.download-btn').addEventListener('click', () => this.downloadImage(image.id));
        div.addEventListener('click', (e) => {
            if (!e.target.closest('.image-actions')) {
                this.viewImage(image.id);
            }
        });
        
        // Charger l'image de manière asynchrone
        this.loadImageThumbnail(image.id, div);
        
        return div;
    }
    
    /**
     * Charger la miniature d'une image
     */
    async loadImageThumbnail(imageId, container) {
        try {
            const response = await this.apiRequest(`/image/${imageId}`, 'GET');
            
            if (response.success && response.data.image_data) {
                const imgElement = container.querySelector('.image-thumbnail');
                const loadingElement = container.querySelector('.image-loading');
                
                imgElement.src = `data:image/png;base64,${response.data.image_data}`;
                imgElement.onload = () => {
                    loadingElement.style.display = 'none';
                    imgElement.style.display = 'block';
                };
                
                // Mettre en cache
                this.imageCache.set(imageId, response.data);
            }
            
        } catch (error) {
            console.error(`Error loading image ${imageId}:`, error);
            const loadingElement = container.querySelector('.image-loading');
            loadingElement.innerHTML = '<i class="fas fa-exclamation-triangle text-warning"></i>';
        }
    }
    
    /**
     * Afficher une image dans le modal
     */
    async viewImage(imageId) {
        try {
            let imageData = this.imageCache.get(imageId);
            
            if (!imageData) {
                const response = await this.apiRequest(`/image/${imageId}`, 'GET');
                if (response.success) {
                    imageData = response.data;
                    this.imageCache.set(imageId, imageData);
                }
            }
            
            if (imageData) {
                this.elements.modalImage.src = `data:image/png;base64,${imageData.image_data}`;
                this.elements.imageInfo.innerHTML = `
                    <p><strong>ID:</strong> ${imageId}</p>
                    <p><strong>Créée le:</strong> ${new Date(imageData.created_at).toLocaleString()}</p>
                `;
                this.elements.downloadBtn.setAttribute('data-image-id', imageId);
                this.elements.imageModal.show();
            }
            
        } catch (error) {
            console.error('View image error:', error);
            this.showNotification('Erreur lors du chargement de l\'image', 'error');
        }
    }
    
    /**
     * Télécharger une image
     */
    async downloadImage(imageId) {
        try {
            let imageData = this.imageCache.get(imageId);
            
            if (!imageData) {
                const response = await this.apiRequest(`/image/${imageId}`, 'GET');
                if (response.success) {
                    imageData = response.data;
                }
            }
            
            if (imageData) {
                const link = document.createElement('a');
                link.href = `data:image/png;base64,${imageData.image_data}`;
                link.download = `ecg_diagnostic_${this.config.diagnosticId}_image_${imageId}.png`;
                link.click();
            }
            
        } catch (error) {
            console.error('Download image error:', error);
            this.showNotification('Erreur lors du téléchargement', 'error');
        }
    }
    
    /**
     * Télécharger l'image actuellement affichée dans le modal
     */
    downloadCurrentImage() {
        const imageId = this.elements.downloadBtn.getAttribute('data-image-id');
        if (imageId) {
            this.downloadImage(imageId);
        }
    }
    
    /**
     * Changer la vue de la galerie
     */
    switchView(viewType) {
        const gallery = this.elements.imageGallery;
        
        if (viewType === 'grid') {
            gallery.className = 'image-gallery-grid';
            this.elements.gridViewBtn.classList.add('active');
            this.elements.listViewBtn.classList.remove('active');
        } else {
            gallery.className = 'image-gallery-list';
            this.elements.listViewBtn.classList.add('active');
            this.elements.gridViewBtn.classList.remove('active');
        }
    }
    
    /**
     * Mettre à jour l'interface utilisateur
     */
    updateUI() {
        if (this.isCapturing) {
            this.elements.startBtn.style.display = 'none';
            this.elements.stopBtn.style.display = 'block';
            this.elements.statusLight.className = 'status-light status-running';
            this.elements.statusText.textContent = 'Capture en cours...';
        } else {
            this.elements.startBtn.style.display = 'block';
            this.elements.stopBtn.style.display = 'none';
            this.elements.statusLight.className = 'status-light status-idle';
            this.elements.statusText.textContent = 'Prêt';
        }
    }
    
    /**
     * Démarrer le timer de capture
     */
    startCaptureTimer() {
        this.captureTimer = setInterval(() => {
            if (this.captureStartTime) {
                const elapsed = Math.floor((Date.now() - this.captureStartTime) / 1000);
                const minutes = Math.floor(elapsed / 60);
                const seconds = elapsed % 60;
                this.elements.captureTime.textContent = 
                    `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }
        }, 1000);
    }
    
    /**
     * Arrêter le timer de capture
     */
    stopCaptureTimer() {
        if (this.captureTimer) {
            clearInterval(this.captureTimer);
            this.captureTimer = null;
        }
    }
    
    /**
     * Mettre à jour le compteur d'images
     */
    updateImageCount(count) {
        this.elements.imageCount.textContent = count;
    }
    
    /**
     * Mettre à jour les informations de session
     */
    updateSessionInfo(sessionInfo) {
        if (sessionInfo.total_images) {
            this.updateImageCount(sessionInfo.total_images);
        }
    }
    
    /**
     * Gérer l'état de chargement des boutons
     */
    setButtonsLoading(loading) {
        const buttons = [this.elements.startBtn, this.elements.stopBtn];
        
        buttons.forEach(btn => {
            if (loading) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Chargement...';
            } else {
                btn.disabled = false;
                if (btn === this.elements.startBtn) {
                    btn.innerHTML = '<i class="fas fa-play me-2"></i>Démarrer la Capture';
                } else {
                    btn.innerHTML = '<i class="fas fa-stop me-2"></i>Arrêter la Capture';
                }
            }
        });
    }
    
    /**
     * Démarrer le polling du statut
     */
    startStatusPolling() {
        this.refreshInterval = setInterval(() => {
            this.checkCaptureStatus();
            if (this.isCapturing) {
                this.refreshGallery();
            }
        }, this.config.refreshInterval);
    }
    
    /**
     * Arrêter le polling
     */
    pausePolling() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }
    
    /**
     * Reprendre le polling
     */
    resumePolling() {
        if (!this.refreshInterval) {
            this.startStatusPolling();
        }
    }
    
    /**
     * Effectuer une requête API
     */
    async apiRequest(endpoint, method = 'GET', data = null) {
        const url = this.config.apiBaseUrl + endpoint;
        
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        if (data && (method === 'POST' || method === 'PUT')) {
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(url, options);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return await response.json();
    }
    
    /**
     * Afficher une notification
     */
    showNotification(message, type = 'info') {
        // Créer une notification Bootstrap
        const alertClass = type === 'error' ? 'alert-danger' : 
                          type === 'success' ? 'alert-success' : 'alert-info';
        
        const notification = document.createElement('div');
        notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove après 5 secondes
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
    
    /**
     * Nettoyer les ressources
     */
    destroy() {
        this.pausePolling();
        this.stopCaptureTimer();
        
        // Nettoyer le cache
        this.imageCache.clear();
        
        console.log('ECG Realtime Controller destroyed');
    }
}

// Export pour utilisation globale
window.ECGRealtimeController = ECGRealtimeController; 