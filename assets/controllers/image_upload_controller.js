import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur Stimulus pour l'upload dynamique d'images avec prévisualisation
 * Génère automatiquement les variantes LiipImagine lors de l'upload
 */
export default class extends Controller {
    static targets = ['input', 'preview', 'previewWrapper', 'variants', 'loading', 'error'];
    static values = {
        uploadUrl: String,
    };

    connect() {
        if (this.hasInputTarget) {
            this.inputTarget.addEventListener('change', this.handleFileSelect.bind(this));
        }
    }

    disconnect() {
        if (this.hasInputTarget) {
            this.inputTarget.removeEventListener('change', this.handleFileSelect.bind(this));
        }
    }

    async handleFileSelect(event) {
        const file = event.target.files?.[0];
        if (!file) {
            return;
        }

        // Validation côté client
        if (!file.type.match(/^image\/(jpeg|webp)$/)) {
            this.showError('Format non supporté. Utilisez JPG ou WEBP.');
            return;
        }

        if (file.size > 4 * 1024 * 1024) {
            this.showError('Le fichier est trop volumineux (max 4 Mo).');
            return;
        }

        // Afficher le loader
        this.showLoading();

        // Créer FormData
        const formData = new FormData();
        formData.append('image', file);

        try {
            const response = await fetch(this.uploadUrlValue || '/admin/blog/upload-image-preview', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Erreur lors de l\'upload');
            }

            // Afficher la prévisualisation
            this.showPreview(data.previewUrl, data.variants);
            this.hideError();
        } catch (error) {
            console.error('Erreur upload image:', error);
            this.showError(error.message || 'Erreur lors de l\'upload de l\'image.');
        } finally {
            this.hideLoading();
        }
    }

    showPreview(previewUrl, variants) {
        if (this.hasPreviewTarget) {
            this.previewTarget.src = previewUrl.startsWith('http') 
                ? previewUrl 
                : `/${previewUrl}`;
        }

        if (this.hasPreviewWrapperTarget) {
            this.previewWrapperTarget.classList.remove('d-none');
        }

        // Afficher les variantes générées (optionnel, pour debug)
        if (this.hasVariantsTarget && variants) {
            const variantsHtml = Object.entries(variants)
                .filter(([_, url]) => url)
                .map(([filter, url]) => {
                    const fullUrl = url.startsWith('http') ? url : `/${url}`;
                    return `<div class="small text-muted mb-1">
                        <strong>${filter}:</strong> 
                        <a href="${fullUrl}" target="_blank" class="text-decoration-none">Voir</a>
                    </div>`;
                })
                .join('');
            
            if (variantsHtml) {
                this.variantsTarget.innerHTML = `
                    <div class="mt-2 p-2 bg-light rounded">
                        <small class="text-muted d-block mb-1">Variantes générées :</small>
                        ${variantsHtml}
                    </div>`;
            }
        }
    }

    showLoading() {
        if (this.hasLoadingTarget) {
            this.loadingTarget.classList.remove('d-none');
        }
    }

    hideLoading() {
        if (this.hasLoadingTarget) {
            this.loadingTarget.classList.add('d-none');
        }
    }

    showError(message) {
        if (this.hasErrorTarget) {
            this.errorTarget.textContent = message;
            this.errorTarget.classList.remove('d-none');
        }
        
        // Réinitialiser l'input
        if (this.hasInputTarget) {
            this.inputTarget.value = '';
        }
    }

    hideError() {
        if (this.hasErrorTarget) {
            this.errorTarget.classList.add('d-none');
            this.errorTarget.textContent = '';
        }
    }

    clearPreview() {
        if (this.hasPreviewTarget) {
            this.previewTarget.src = '#';
        }
        if (this.hasPreviewWrapperTarget) {
            this.previewWrapperTarget.classList.add('d-none');
        }
        if (this.hasInputTarget) {
            this.inputTarget.value = '';
        }
        this.hideError();
    }
}

