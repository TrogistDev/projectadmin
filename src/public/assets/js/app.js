
const App = {
  user: null,
  projects: [],
  users: [],
  currentProjectId: null,
  currentView: "list",

init() {
    ModalCreateProject.init();
    ModalEditProject.init();
    ModalCreateUser.init();
    this.bindEvents();

    const savedUser = sessionStorage.getItem('user');
    if (savedUser) {
        try {
            this.user = JSON.parse(savedUser);
            Auth.showDashboard();
            Projects.loadProjects();
            Users.loadUsers();
            return;
        } catch (e) {
            console.warn('Sessão inválida, limpando...', e);
            sessionStorage.removeItem('user');
        }
    }
    Auth.showLogin();
},

  bindEvents() {
    $("#login-form").on("submit", (e) => { e.preventDefault(); Auth.handleLogin(); });
    $("#logout-button").on("click", () => Auth.handleLogout());
    $("#refresh-projects").on("click", () => Projects.loadProjects());

    $("input[name='view-type']").on("change", (event) => {
      this.currentView = $(event.target).val();
      this.switchView();
    });

    $(".card-header").on("click", "#create-project-button", () => Projects.showCreateProjectModal());
    $("#create-project-form").on("submit", (e) => { e.preventDefault(); Projects.handleCreateProject(); });
    $("#create-project-modal").on("click", "#add-phase-field-btn", (e) => { e.preventDefault(); Projects.appendPhaseField(); });
    $("#project-phases-inputs-container").on("click", ".remove-phase-field-btn", (e) => { e.preventDefault(); $(e.target).closest(".dynamic-phase-input").remove(); });
    
    $(document).off("submit", "#create-phase-form").on("submit", "#create-phase-form", (e) => { 
      e.preventDefault(); 
      Projects.handleCreatePhase(); 
    });

    $("#admin-users-button").on("click", () => {
      bootstrap.Modal.getOrCreateInstance(document.getElementById("create-user-modal")).show();
    });
    $("#create-user-form").on("submit", (e) => { e.preventDefault(); Users.handleCreateUser(); });

    $("#dashboard-screen").off("click", ".view-project-btn").on("click", ".view-project-btn", (event) => {
      const id = $(event.target).data("id");
      if (id) Projects.openProject(id);
    });

    $("#close-detail").on("click", () => this.closeDetail());

    $("#detail-body").on("click", "#open-edit-project-modal-btn", (event) => {
      const id = $(event.target).data("id");
      Projects.showEditProjectModal(id);
    });

    $("#edit-project-form").on("submit", (e) => { e.preventDefault(); Projects.handleEditProject(); });

    $("#detail-body").on("change", ".toggle-phase-checkbox", (event) => {
      const $checkbox = $(event.target);
      const phaseId = $checkbox.data("phase-id");
      const projectId = $checkbox.data("project-id");
      const isChecked = $checkbox.is(":checked");
      Projects.handleTogglePhase(projectId, phaseId, isChecked, $checkbox);
    });

    $("#dashboard-screen").on("click", ".pause-project-btn", (event) => {
      event.stopPropagation();
      const id = $(event.target).data("id");
      const isCurrentlyPaused = $(event.target).data("paused") === true;
      Projects.handleTogglePauseProject(id, isCurrentlyPaused);
    });

    $("#dashboard-screen").on("click", ".delete-project-btn", (event) => {
      event.stopPropagation();
      const id = $(event.target).data("id");
      Projects.handleDeleteProject(id);
    });

    $("#open-filter-modal-btn").on("click", () => {
      const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("filter-modal"));
      modal.show();
    });

    $("#apply-filters-btn").on("click", () => {
      Projects.applyFilters();
      bootstrap.Modal.getInstance(document.getElementById("filter-modal")).hide();
    });
  },

  showLogin() { Auth.showLogin(); },
  showDashboard() { Auth.showDashboard(); },

  closeDetail() {
    this.currentProjectId = null;
    $("#project-detail").addClass("d-none");
  },

  renderHeaderActions() {
    const $buttonContainer = $("#create-project-button-container");
    $buttonContainer.empty();
    const secObj = window.Security || Security;
    if (secObj && typeof secObj.canCreateProject === "function") {
      if (secObj.canCreateProject()) {
        $buttonContainer.html('<button class="btn btn-primary" id="create-project-button">Novo projeto</button>');
      }
    }
  },

  switchView() {
    $("#list-view, #calendar-view, #timeline-view").addClass("d-none");
    $(`#${this.currentView}-view`).removeClass("d-none");
  },

  showFeedback(message, type = "info", target = "#login-feedback") {
    $(target).html(`
      <div class="alert alert-${type} alert-dismissible fade show" role="alert">
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>`);
  }
};

$(document).ready(() => App.init());