class ExamSecurity {
    constructor() {
        this.warningCount = 0;
        this.maxWarnings = 3;
        this.lastActiveTime = Date.now();
        this.isFullScreen = false;
        this.initializeSecurityMeasures();
        this.checkFullScreenStatus();
    }

    initializeSecurityMeasures() {
        // Désactiver le clic droit
        document.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            this.logSecurityEvent('right_click_attempt');
            alert("Le clic droit est désactivé pendant l'examen.");
        });

        // Désactiver les raccourcis clavier
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey && (e.key === 'c' || e.key === 'v' || e.key === 'x')) || 
                (e.altKey && e.key === 'Tab')) {
                e.preventDefault();
                this.logSecurityEvent('keyboard_shortcut_attempt');
                alert("Les raccourcis clavier sont désactivés pendant l'examen.");
            }
        });

        // Détecter changement de visibilité
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.handlePageVisibilityChange();
            }
        });

        // Surveiller la perte de focus
        window.addEventListener('blur', () => {
            this.handleWindowBlur();
        });

        // Surveiller l'activité
        document.addEventListener('mousemove', () => this.updateLastActiveTime());
        document.addEventListener('keydown', () => this.updateLastActiveTime());
        document.addEventListener('mousedown', () => this.updateLastActiveTime());

        // Vérification périodique
        setInterval(() => {
            this.checkActivity();
            this.checkFullScreenStatus();
        }, 1000);

        // Empêcher de quitter la page
        window.addEventListener('beforeunload', (e) => {
            this.logSecurityEvent('page_exit_attempt');
            e.preventDefault();
            e.returnValue = "Attention : Quitter cette page mettra fin à votre examen. Êtes-vous sûr ?";
        });

        // Bloquer copier-coller
        document.addEventListener('copy', (e) => {
            e.preventDefault();
            this.logSecurityEvent('copy_attempt');
            alert("La copie est désactivée pendant l'examen.");
        });

        document.addEventListener('paste', (e) => {
            e.preventDefault();
            this.logSecurityEvent('paste_attempt');
            alert("Le collage est désactivé pendant l'examen.");
        });

        // Gérer le plein écran
        document.addEventListener('fullscreenchange', () => {
            this.isFullScreen = !!document.fullscreenElement;
            if (!this.isFullScreen) {
                this.logSecurityEvent('fullscreen_exit');
                alert("Veuillez rester en mode plein écran pendant l'examen.");
                this.requestFullscreen();
            }
        });
    }

    updateLastActiveTime() {
        this.lastActiveTime = Date.now();
    }

    checkFullScreenStatus() {
        const isFullScreen = document.fullscreenElement !== null;
        if (!isFullScreen && this.isFullScreen !== false) {
            this.logSecurityEvent('window_minimized');
            this.isFullScreen = false;
            alert("Attention : Vous devez rester en mode plein écran pendant l'examen.");
            this.requestFullscreen();
        }
    }

    handleWindowBlur() {
        this.warningCount++;
        this.logSecurityEvent('window_blur');
        
        if (this.warningCount >= this.maxWarnings) {
            alert("ATTENTION : Vous avez changé de fenêtre trop de fois. Cet incident sera signalé à votre professeur.");
        } else {
            alert(`Attention : Changer de fenêtre pendant l'examen n'est pas autorisé. Avertissement ${this.warningCount}/${this.maxWarnings}`);
        }
    }

    checkActivity() {
        const inactiveTime = Date.now() - this.lastActiveTime;
        if (inactiveTime > 5000 && !document.hasFocus()) {
            this.handleWindowBlur();
        }
    }

    handlePageVisibilityChange() {
        this.logSecurityEvent('visibility_change');
        alert("Attention : Changer de fenêtre pendant l'examen n'est pas autorisé.");
    }

    logSecurityEvent(eventType) {
        const timestamp = new Date().toISOString();
        fetch('log_security_event.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                event_type: eventType,
                timestamp: timestamp
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Erreur lors de l\'enregistrement de l\'événement:', data.message);
            }
        })
        .catch(error => {
            console.error('Erreur lors de l\'envoi de l\'événement:', error);
        });
    }

    requestFullscreen() {
        const elem = document.documentElement;
        if (elem.requestFullscreen) {
            elem.requestFullscreen().catch(e => {
                console.error('Erreur lors du passage en plein écran:', e);
                this.logSecurityEvent('fullscreen_error');
            });
        } else if (elem.webkitRequestFullscreen) {
            elem.webkitRequestFullscreen();
        } else if (elem.msRequestFullscreen) {
            elem.msRequestFullscreen();
        }
    }

    startExamMode() {
        this.requestFullscreen();
        this.logSecurityEvent('exam_started');
    }
}

// Initialiser la sécurité
document.addEventListener('DOMContentLoaded', () => {
    const examSecurity = new ExamSecurity();
    examSecurity.startExamMode();
});
