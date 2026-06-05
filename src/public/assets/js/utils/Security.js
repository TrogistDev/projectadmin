const Security = {
    getCurrentRole() {
        try {
            const sessionData = sessionStorage.getItem('user');
            if (!sessionData) return 'colaborador';
            const user = JSON.parse(sessionData);
            return user.rol || user.role || 'colaborador';
        } catch (e) {
            return 'colaborador';
        }
    },

    canCreateProject() {
        return this.getCurrentRole() === 'administrador' || this.getCurrentRole() === 'jefe_proyecto';
    },

    canManageUsers() {
        return this.getCurrentRole() === 'administrador';
    },

    canEditProject(isResponsible) {
        const role = this.getCurrentRole();
        return role === 'administrador' || (role === 'jefe_proyecto' && isResponsible);
    },

    canManageMembers() {
        const role = this.getCurrentRole();
        return role === 'administrador' || role === 'jefe_proyecto';
    },

    canEditPhase(isResponsible, isMember) {
        const role = this.getCurrentRole();
        if (role === 'administrador') return true;
        if (role === 'jefe_proyecto') return isResponsible;
        if (role === 'colaborador') return isMember;
        return false;
    }
};

window.Security = Security;
