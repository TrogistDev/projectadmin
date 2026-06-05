// ❌ REMOVA: export const Security = {
//  ALTERE PARA:
const Security = {
    // Traz a role do usuário atualmente logado armazenada com segurança
    getCurrentRole() {
        try {
            const sessionData = sessionStorage.getItem('user');
            if (!sessionData) return 'colaborador';
            
            const user = JSON.parse(sessionData);
            return user.rol || user.role || 'colaborador';
        } catch (e) {
            console.error("Erro ao ler credenciais do escopo de sessão:", e);
            return 'colaborador';
        }
    },

    // Espelho rígido das permissões do Backend
    canCreateProject() {
        const role = this.getCurrentRole();
        return role === 'administrador' || role === 'jefe_proyecto';
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

// Se precisar expor globalmente caso os scripts estejam isolados:
window.Security = Security;