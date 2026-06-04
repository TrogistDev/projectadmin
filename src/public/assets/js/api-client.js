const ApiClient = {
    request(method, path, body = null) {
        return $.ajax({
            url: path,
            method,
            data: body ? JSON.stringify(body) : null,
            contentType: 'application/json',
            dataType: 'json'
        });
    },

    login(email, password) {
        return this.request('POST', '/api/login', { correo: email, contrasena: password });
    },

    logout() {
        return this.request('POST', '/api/logout');
    },

    getProjects() {
        return this.request('GET', '/api/projects');
    },

    getProject(projectId) {
        return this.request('GET', `/api/projects/${projectId}`);
    }
};
