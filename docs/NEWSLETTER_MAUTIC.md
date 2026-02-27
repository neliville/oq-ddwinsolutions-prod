# Intégration Newsletter Mautic

L'inscription à la newsletter envoie les contacts vers Mautic via son API REST (Basic Auth).
Mautic gère l'automatisation et l'envoi des emails.

## Configuration

Variables d'environnement (`.env`) :

```env
MAUTIC_URL=https://mautic.outils-qualite.com
MAUTIC_USER=votre_utilisateur_api
MAUTIC_PASSWORD=votre_mot_de_passe_api
```

## Endpoints

- `POST /newsletter/subscribe` – Nouveau endpoint (JSON ou form-data)
- `POST /api/newsletter/subscribe` – Endpoint existant (formulaire Symfony, même logique Mautic)

## Exemple Twig (formulaire)

```twig
{{ form_start(newsletterForm, {
    'action': path('app_newsletter_mautic_subscribe'),
    'attr': {
        'id': 'newsletter-form',
        'data-turbo': 'false',
        'class': 'newsletter-form'
    }
}) }}
    {{ form_row(newsletterForm.email, { label: false, attr: { placeholder: 'Votre email' } }) }}
    {{ form_row(newsletterForm.firstname, { label: false, attr: { placeholder: 'Prénom (optionnel)' } }) }}
    <button type="submit">S'inscrire</button>
{{ form_end(newsletterForm) }}
```

## Exemple JS fetch()

```javascript
document.getElementById('newsletter-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const response = await fetch('/newsletter/subscribe', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
            email: formData.get('email'),
            firstname: formData.get('firstname') || null
        })
    });
    const data = await response.json();
    if (data.success) {
        // Succès
    } else {
        // Afficher data.message ou data.errors
    }
});
```

## Réponses JSON

- **201** : `{"success": true, "message": "Vous êtes maintenant abonné à notre newsletter !"}`
- **400** : `{"success": false, "message": "...", "errors": [...]}`
- **503** : Message générique en cas d'erreur Mautic
