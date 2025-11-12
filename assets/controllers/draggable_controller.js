import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

/**
 * Contrôleur Stimulus pour le drag & drop des catégories
 * Séparé de la logique métier pour une meilleure séparation des responsabilités
 */
export default class extends Controller {
    static values = {
        categoryId: Number,
    };

    connect() {
        this.isDragging = false;
        this.offset = { x: 0, y: 0 };
        
        // Bind les méthodes pour pouvoir les retirer plus tard
        this.boundDrag = this.drag.bind(this);
        this.boundEndDrag = this.endDrag.bind(this);
    }

    disconnect() {
        // Nettoyer les event listeners si le composant est déconnecté pendant un drag
        if (this.isDragging) {
            document.removeEventListener('mousemove', this.boundDrag);
            document.removeEventListener('mouseup', this.boundEndDrag);
        }
    }

    startDrag(event) {
        // Ignorer si on clique sur les boutons d'action
        if (event.target.closest('.ishikawa-category-actions')) {
            return;
        }
        
        // Ignorer si on clique sur une cause ou un bouton dans la zone des causes
        if (event.target.closest('.cause-item') || event.target.closest('.category-causes button')) {
            return;
        }
        
        this.isDragging = true;
        
        // Calculer l'offset initial
        const rect = this.element.getBoundingClientRect();
        const container = this.element.parentElement;
        const containerRect = container.getBoundingClientRect();
        
        this.offset = {
            x: event.clientX - rect.left,
            y: event.clientY - rect.top
        };
        
        this.element.classList.add('dragging');
        this.element.style.zIndex = '1000';
        
        // Ajouter les listeners temporairement
        document.addEventListener('mousemove', this.boundDrag);
        document.addEventListener('mouseup', this.boundEndDrag);
        
        event.preventDefault();
        event.stopPropagation();
    }

    drag(event) {
        if (!this.isDragging) return;
        
        const container = this.element.parentElement;
        const containerRect = container.getBoundingClientRect();
        
        // Calculer la nouvelle position
        let x = event.clientX - containerRect.left - this.offset.x;
        let y = event.clientY - containerRect.top - this.offset.y;
        
        // Contraintes pour rester dans le conteneur
        const minX = 0;
        const minY = 0;
        const maxX = container.offsetWidth - this.element.offsetWidth;
        const maxY = container.offsetHeight - this.element.offsetHeight;
        
        x = Math.max(minX, Math.min(x, maxX));
        y = Math.max(minY, Math.min(y, maxY));
        
        // Convertir en pourcentage pour la cohérence avec le système
        const percentX = (x / container.offsetWidth) * 100;
        const percentY = (y / container.offsetHeight) * 100;
        
        // Mettre à jour visuellement
        this.element.style.left = percentX + '%';
        this.element.style.top = percentY + '%';
        
        // Émettre un événement pour mettre à jour les lignes de connexion
        this.dispatch('moved', { detail: { x: percentX, y: percentY } });
    }

    async endDrag(event) {
        if (!this.isDragging) return;
        
        this.isDragging = false;
        this.element.classList.remove('dragging');
        this.element.style.zIndex = '';
        
        // Retirer les listeners
        document.removeEventListener('mousemove', this.boundDrag);
        document.removeEventListener('mouseup', this.boundEndDrag);
        
        // Récupérer la position finale en pourcentage
        const container = this.element.parentElement;
        const rect = this.element.getBoundingClientRect();
        const containerRect = container.getBoundingClientRect();
        
        const percentX = ((rect.left - containerRect.left) / container.offsetWidth) * 100;
        const percentY = ((rect.top - containerRect.top) / container.offsetHeight) * 100;
        
        // Sauvegarder la position via le Live Component
        try {
            const liveComponent = this.element.closest('[data-live-id]');
            if (liveComponent) {
                const component = await getComponent(liveComponent);
                await component.action('updateCategoryPosition', {
                    categoryId: this.categoryIdValue,
                    left: percentX + '%',
                    top: percentY + '%'
                });
            }
        } catch (error) {
            console.error('Erreur lors de la sauvegarde de la position:', error);
        }
    }
}

