// public/assets/js/api-client.js

const ApiClient = {
  BASE_URL: "/api",

  /**
   * Método genérico e rígido para requisições HTTP via jQuery AJAX
   */
  request(method, path, body = null) {
    const url = `${this.BASE_URL}${path}`;

    return $.ajax({
      url,
      method,
      data: body ? JSON.stringify(body) : null,
      contentType: "application/json",
      dataType: "json",
      xhrFields: { withCredentials: true },
    }).fail((xhr) => {
      console.error(`API Error [${method} ${url}]`, xhr.status, xhr.responseText);
    });
  },

  // ==========================
  // AUTH ENDPOINTS
  // ==========================
  login(email, password) {
    return this.request("POST", "/login", {
      correo: email,
      contrasena: password,
    });
  },

  logout() {
    return this.request("POST", "/logout");
  },

  // ==========================
  // USER ENDPOINTS
  // ==========================
  getUsers() {
    // Casamento estrito com: /api/users
    return this.request("GET", "/users");
  },

  createUser(data) {
    return this.request("POST", "/users", data);
  },

  // ==========================
  // PROJECT ENDPOINTS
  // ==========================
  getProjects() {
    return this.request("GET", "/projects");
  },

  getProject(id) {
    return this.request("GET", `/projects/${id}`);
  },

  createProject(data) {
    return this.request("POST", "/projects", data);
  },

  updateProject(projectId, data) {
    return this.request("PUT", `/projects/${projectId}`, data);
  },

  deleteProject(projectId) {
    return this.request("DELETE", `/projects/${projectId}`);
  },

  // ==========================
  // PROJECT MEMBERS (TEAM)
  // ==========================
  getProjectMembers(projectId) {
    return this.request("GET", `/projects/${projectId}/members`);
  },

  addMember(projectId, userId, roleEspecifico) {
    return this.request("POST", `/projects/${projectId}/members`, {
      usuario_id: userId,
      rol_especifico: roleEspecifico || "colaborador", // Corrigido para bater com o banco
    });
  },

  removeMember(projectId, userId) {
    return this.request("DELETE", `/projects/${projectId}/members/${userId}`);
  },

  // ==========================
  // PROJECT PHASES
  // ==========================
  getPhases(projectId) {
    return this.request("GET", `/projects/${projectId}/phases`);
  },

  createPhase(projectId, data) {
    // Casamento estrito com: POST /api/projects/{id}/phases
    return this.request("POST", `/projects/${projectId}/phases`, data);
  },

  togglePhase(phaseId, completada) {
    // Casamento estrito com: PUT /api/phases/{id}
    return this.request("PUT", `/phases/${phaseId}`, {
      completada: completada ? 1 : 0
    });
  },

  updatePhase(phaseId, data) {
    return this.request("PUT", `/phases/${phaseId}`, data);
  },

  deletePhase(phaseId) {
    return this.request("DELETE", `/phases/${phaseId}`);
  },
  toggleProjectPause(projectId, isToPause) {
    return this.request("PUT", `/projects/${projectId}/pause`, {
        pausar: isToPause
    });
}
};