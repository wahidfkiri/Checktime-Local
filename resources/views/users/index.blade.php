@extends('layouts.app')

@section('content')
<div id="main" class="layout-navbar navbar-fixed">
    <x-nav-bar />
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Gestion des utilisateurs</h3>
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('home') }}">Tableau de bord</a>
                                </li>
                                <li class="breadcrumb-item active">Utilisateurs</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <section class="section">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Comptes utilisateurs</span>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createUserModal">
                            <i class="bi bi-plus-circle me-1"></i> Nouvel utilisateur
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-1"></i>
                            Un administrateur voit tout le menu. Un utilisateur normal ne voit et
                            n'accède qu'aux pages dont la permission lui a été assignée ci-dessous.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Email</th>
                                        <th>Accès</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                    <tr>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>
                                            @if($user->hasRole('admin'))
                                                <span class="badge bg-primary">Administrateur (accès complet)</span>
                                            @elseif($user->permissions->isEmpty())
                                                <span class="badge bg-secondary">Aucun accès assigné</span>
                                            @else
                                                @foreach($user->permissions as $permission)
                                                    <span class="badge bg-light text-dark border">{{ $permission->name }}</span>
                                                @endforeach
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-warning edit-user-btn"
                                                        data-id="{{ $user->id }}" title="Gérer les permissions">
                                                    <i class="bi bi-shield-lock"></i>
                                                </button>
                                                @if($user->id !== auth()->id())
                                                <button type="button" class="btn btn-sm btn-danger delete-user-btn"
                                                        data-id="{{ $user->id }}" data-name="{{ $user->name }}" title="Supprimer">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<!-- Modal de création d'utilisateur -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createUserModalLabel">Nouvel utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createUserForm">
                @csrf
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required maxlength="255">
                            <div class="invalid-feedback" id="name-error"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required maxlength="255">
                            <div class="invalid-feedback" id="email-error"></div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="8">
                            <div class="invalid-feedback" id="password-error"></div>
                        </div>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="is_admin" name="is_admin" value="1">
                        <label class="form-check-label" for="is_admin">
                            <strong>Administrateur</strong> — accès complet à toutes les pages
                        </label>
                    </div>

                    <div id="create-permissions-block">
                        <label class="form-label">Pages accessibles</label>
                        @foreach($permissionGroups as $groupName => $permissions)
                            <div class="mb-2">
                                <div class="submenu-header">{{ $groupName }}</div>
                                <div class="row">
                                    @foreach($permissions as $permName => $permLabel)
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input create-permission-checkbox" type="checkbox"
                                                       id="create_perm_{{ $loop->parent->index }}_{{ $loop->index }}"
                                                       name="permissions[]" value="{{ $permName }}">
                                                <label class="form-check-label" for="create_perm_{{ $loop->parent->index }}_{{ $loop->index }}">
                                                    {{ $permLabel }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submit-create-user">
                        <span id="create-user-text">Créer</span>
                        <span id="create-user-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal d'édition des permissions -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Modifier l'utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editUserForm">
                @csrf
                <input type="hidden" id="edit_user_id">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_name" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name" name="name" required maxlength="255">
                            <div class="invalid-feedback" id="edit_name-error"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="edit_email" name="email" required maxlength="255">
                            <div class="invalid-feedback" id="edit_email-error"></div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_password" class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control" id="edit_password" name="password" minlength="8">
                            <div class="form-text">Laisser vide pour ne pas changer le mot de passe</div>
                            <div class="invalid-feedback" id="edit_password-error"></div>
                        </div>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="edit_is_admin" name="is_admin" value="1">
                        <label class="form-check-label" for="edit_is_admin">
                            <strong>Administrateur</strong> — accès complet à toutes les pages
                        </label>
                    </div>

                    <div id="edit-permissions-block">
                        <label class="form-label">Pages accessibles</label>
                        @foreach($permissionGroups as $groupName => $permissions)
                            <div class="mb-2">
                                <div class="submenu-header">{{ $groupName }}</div>
                                <div class="row">
                                    @foreach($permissions as $permName => $permLabel)
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input edit-permission-checkbox" type="checkbox"
                                                       id="edit_perm_{{ $loop->parent->index }}_{{ $loop->index }}"
                                                       name="permissions[]" value="{{ $permName }}">
                                                <label class="form-check-label" for="edit_perm_{{ $loop->parent->index }}_{{ $loop->index }}">
                                                    {{ $permLabel }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submit-edit-user">
                        <span id="edit-user-text">Enregistrer</span>
                        <span id="edit-user-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de suppression -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteUserModalLabel">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer l'utilisateur <strong id="delete-user-name"></strong> ?</p>
                <p class="text-danger"><small>Cette action est irréversible.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-user">
                    <span id="delete-user-text">Supprimer</span>
                    <span id="delete-user-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    let userToDelete = null;

    function showSweetAlert(icon, title, text, timer = null) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: timer || 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        Toast.fire({ icon: icon, title: title, text: text });
    }

    function toggleAdminBlock(adminCheckbox, permissionsBlock) {
        const isAdmin = adminCheckbox.is(':checked');
        permissionsBlock.find('input[type=checkbox]').prop('disabled', isAdmin);
        permissionsBlock.css('opacity', isAdmin ? 0.5 : 1);
    }

    // --- Création ---

    toggleAdminBlock($('#is_admin'), $('#create-permissions-block'));
    $('#is_admin').on('change', function() {
        toggleAdminBlock($(this), $('#create-permissions-block'));
    });

    $('#createUserModal').on('hidden.bs.modal', function() {
        $('#createUserForm')[0].reset();
        $('.invalid-feedback').text('');
        $('.form-control').removeClass('is-invalid');
        toggleAdminBlock($('#is_admin'), $('#create-permissions-block'));
    });

    $('#createUserForm').on('submit', function(e) {
        e.preventDefault();

        const submitBtn = $('#submit-create-user');
        const spinner = $('#create-user-spinner');
        const text = $('#create-user-text');

        submitBtn.prop('disabled', true);
        spinner.removeClass('d-none');
        text.text('Création...');

        $('#createUserForm .invalid-feedback').text('');
        $('#createUserForm .form-control').removeClass('is-invalid');

        $.ajax({
            url: "{{ route('users.store') }}",
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    showSweetAlert('success', 'Succès', 'Utilisateur créé avec succès');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showSweetAlert('error', 'Erreur', response.message);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(function(key) {
                        $(`#${key}-error`).text(errors[key][0]);
                        $(`#${key}`).addClass('is-invalid');
                    });
                    showSweetAlert('error', 'Erreur de validation', 'Veuillez corriger les erreurs du formulaire');
                } else {
                    showSweetAlert('error', 'Erreur', 'Une erreur est survenue');
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false);
                spinner.addClass('d-none');
                text.text('Créer');
            }
        });
    });

    // --- Édition ---

    toggleAdminBlock($('#edit_is_admin'), $('#edit-permissions-block'));
    $('#edit_is_admin').on('change', function() {
        toggleAdminBlock($(this), $('#edit-permissions-block'));
    });

    $(document).on('click', '.edit-user-btn', function() {
        const userId = $(this).data('id');

        $.ajax({
            url: "{{ url('users') }}/" + userId,
            type: 'GET',
            success: function(response) {
                if (!response.success) return;
                const user = response.data;

                $('#edit_user_id').val(user.id);
                $('#edit_name').val(user.name);
                $('#edit_email').val(user.email);
                $('#edit_password').val('');
                $('#edit_is_admin').prop('checked', user.is_admin);

                $('.edit-permission-checkbox').prop('checked', false);
                (user.permissions || []).forEach(function(permName) {
                    $(`.edit-permission-checkbox[value="${permName}"]`).prop('checked', true);
                });

                toggleAdminBlock($('#edit_is_admin'), $('#edit-permissions-block'));

                $('.invalid-feedback').text('');
                $('#editUserForm .form-control').removeClass('is-invalid');

                $('#editUserModal').modal('show');
            },
            error: function() {
                showSweetAlert('error', 'Erreur', "Impossible de charger l'utilisateur");
            }
        });
    });

    $('#editUserForm').on('submit', function(e) {
        e.preventDefault();

        const userId = $('#edit_user_id').val();
        const submitBtn = $('#submit-edit-user');
        const spinner = $('#edit-user-spinner');
        const text = $('#edit-user-text');

        submitBtn.prop('disabled', true);
        spinner.removeClass('d-none');
        text.text('Enregistrement...');

        $('#editUserForm .invalid-feedback').text('');
        $('#editUserForm .form-control').removeClass('is-invalid');

        $.ajax({
            url: "{{ url('users') }}/" + userId,
            type: 'POST',
            data: $(this).serialize() + '&_method=PUT',
            success: function(response) {
                if (response.success) {
                    showSweetAlert('success', 'Succès', 'Utilisateur mis à jour avec succès');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showSweetAlert('error', 'Erreur', response.message);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(function(key) {
                        $(`#edit_${key}-error`).text(errors[key][0]);
                        $(`#edit_${key}`).addClass('is-invalid');
                    });
                    showSweetAlert('error', 'Erreur de validation', 'Veuillez corriger les erreurs du formulaire');
                } else if (xhr.status === 422) {
                    showSweetAlert('error', 'Erreur', xhr.responseJSON.message);
                } else {
                    showSweetAlert('error', 'Erreur', 'Une erreur est survenue');
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false);
                spinner.addClass('d-none');
                text.text('Enregistrer');
            }
        });
    });

    // --- Suppression ---

    $(document).on('click', '.delete-user-btn', function() {
        userToDelete = $(this).data('id');
        $('#delete-user-name').text($(this).data('name'));
        $('#deleteUserModal').modal('show');
    });

    $('#confirm-delete-user').on('click', function() {
        const deleteBtn = $(this);
        const spinner = $('#delete-user-spinner');
        const text = $('#delete-user-text');

        deleteBtn.prop('disabled', true);
        spinner.removeClass('d-none');
        text.text('Suppression...');

        $.ajax({
            url: "{{ url('users') }}/" + userToDelete,
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                _method: 'DELETE'
            },
            success: function(response) {
                if (response.success) {
                    showSweetAlert('success', 'Succès', 'Utilisateur supprimé avec succès');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showSweetAlert('error', 'Erreur', response.message);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON) {
                    showSweetAlert('error', 'Erreur', xhr.responseJSON.message);
                } else {
                    showSweetAlert('error', 'Erreur', 'Une erreur est survenue');
                }
            },
            complete: function() {
                deleteBtn.prop('disabled', false);
                spinner.addClass('d-none');
                text.text('Supprimer');
                $('#deleteUserModal').modal('hide');
            }
        });
    });
});
</script>
@endsection
