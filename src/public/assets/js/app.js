const App = {
    user: null,
    projects: [],

    init() {
        this.bindEvents();
        this.showLogin();
    },

    bindEvents() {
        $('#login-form').on('submit', event => {
            event.preventDefault();
            this.handleLogin();
        });

        $('#logout-button').on('click', () => this.handleLogout());
        $('#refresh-projects').on('click', () => this.loadProjects());
        $('#project-search').on('input', () => this.renderProjects());
        $('#project-state').on('change', () => this.renderProjects());
        $('#close-detail').on('click', () => this.closeDetail());
        $('#create-project-button').on('click', () => this.showFeedback('Criação de projeto será implementada no backend.', 'info'));
    },

    handleLogin() {
        const email = $('#login-email').val();
        const password = $('#login-password').val();

        ApiClient.login(email, password)
            .done(response => {
                this.user = response.user;
                this.showDashboard();
                this.loadProjects();
            })
            .fail(xhr => {
                const message = xhr.responseJSON?.error || 'Erro no login.';
                this.showFeedback(message, 'danger', '#login-feedback');
            });
    },

    handleLogout() {
        ApiClient.logout().always(() => {
            this.user = null;
            this.showLogin();
        });
    },

    showLogin() {
        $('#login-screen').removeClass('d-none');
        $('#dashboard-screen').addClass('d-none');
    },

    showDashboard() {
        $('#login-screen').addClass('d-none');
        $('#dashboard-screen').removeClass('d-none');
        this.showFeedback('Bem-vindo(a), ' + this.user.nombre + '!', 'success', '#dashboard-feedback');
    },

    loadProjects() {
        ApiClient.getProjects()
            .done(response => {
                this.projects = response;
                this.renderProjects();
                this.renderSummary();
            })
            .fail(xhr => {
                const message = xhr.responseJSON?.error || 'Erro ao carregar projetos.';
                this.showFeedback(message, 'danger', '#dashboard-feedback');
            });
    },

    renderProjects() {
        const search = $('#project-search').val().toLowerCase();
        const status = $('#project-state').val();
        const rows = this.projects.filter(project => {
            const matchesName = project.nombre.toLowerCase().includes(search);
            const matchesStatus = !status || project.estado === status;
            return matchesName && matchesStatus;
        }).map(project => {
            return `
                <tr>
                    <td>${project.nombre}</td>
                    <td>${project.responsable_nombre || project.responsable_id}</td>
                    <td>${this.statusLabel(project.estado)}</td>
                    <td>
                        <div class="progress" style="height: 18px;">
                            <div class="progress-bar" role="progressbar" style="width: ${project.porcentaje_avance}%" aria-valuenow="${project.porcentaje_avance}" aria-valuemin="0" aria-valuemax="100">${project.porcentaje_avance}%</div>
                        </div>
                    </td>
                    <td><button class="btn btn-sm btn-outline-primary" data-project-id="${project.id}">Ver</button></td>
                </tr>`;
        });

        $('#projects-table tbody').html(rows.join(''));
        $('#projects-table tbody button').on('click', event => {
            const projectId = $(event.currentTarget).data('project-id');
            this.openProject(projectId);
        });
    },

    renderSummary() {
        const count = this.projects.reduce((acc, project) => {
            acc[project.estado] = (acc[project.estado] || 0) + 1;
            return acc;
        }, {});

        const labels = [
            { key: 'planificacion', title: 'Planejamento', color: 'secondary' },
            { key: 'en_curso', title: 'Em curso', color: 'info' },
            { key: 'pausado', title: 'Pausado', color: 'warning' },
            { key: 'finalizado', title: 'Finalizado', color: 'success' }
        ];

        const cards = labels.map(item => `
            <div class="col-md-3 mb-2">
                <div class="card text-white bg-${item.color} h-100">
                    <div class="card-body">
                        <h6>${item.title}</h6>
                        <h3>${count[item.key] || 0}</h3>
                    </div>
                </div>
            </div>
        `);

        $('#summary-cards').html(cards.join(''));
    },

    statusLabel(status) {
        switch (status) {
            case 'planificacion': return 'Planejamento';
            case 'en_curso': return 'Em curso';
            case 'pausado': return 'Pausado';
            case 'finalizado': return 'Finalizado';
            default: return status;
        }
    },

    openProject(projectId) {
        ApiClient.getProject(projectId)
            .done(project => {
                const phases = project.phases.map(phase => `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>${phase.nombre}</span>
                        <span class="badge ${phase.completada ? 'bg-success' : 'bg-secondary'}">${phase.completada ? 'Concluída' : 'Aberta'}</span>
                    </li>
                `).join('');

                const members = project.members.map(member => `
                    <li class="list-group-item">${member.nombre} ${member.apellidos} — ${member.rol_especifico}</li>
                `).join('');

                const html = `
                    <div class="row">
                        <div class="col-md-6">
                            <h5>${project.nombre}</h5>
                            <p>${project.descripcion}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Estado:</strong> ${this.statusLabel(project.estado)}</p>
                            <p><strong>Percentual:</strong> ${project.porcentaje_avance}%</p>
                            <p><strong>Responsável:</strong> ${project.responsable_nombre || project.responsable_id}</p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Fases</h6>
                            <ul class="list-group">${phases}</ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Equipe</h6>
                            <ul class="list-group">${members}</ul>
                        </div>
                    </div>
                `;

                $('#detail-body').html(html);
                $('#project-detail').removeClass('d-none');
            })
            .fail(xhr => {
                const message = xhr.responseJSON?.error || 'Erro ao abrir projeto.';
                this.showFeedback(message, 'danger', '#dashboard-feedback');
            });
    },

    closeDetail() {
        $('#project-detail').addClass('d-none');
    },

    showFeedback(message, type = 'info', target = '#login-feedback') {
        const alert = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        $(target).html(alert);
    }
};

$(document).ready(() => App.init());
