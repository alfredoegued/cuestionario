@extends('layouts.app')

@section('page_title','Usuarios')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Listado de Usuarios</h3>
            <div class="card-tools">
                <ul class="nav nav-pills ml-auto">
                    <li class="nav-item">
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modal-default">
                            <i class="fas fa-plus"></i>
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <div class="card-body">
            <table id="users-table" class="table table-striped nowrap" style="width: 100%;">
                <thead>
                <tr>
                    <td>ID</td>
                    <td>Nombre</td>
                    <td>Nombre de Usuario</td>
                    <td>Email</td>
                    <td>Fecha</td>
                    <td>Acciones</td>
                </tr>
                </thead>
            </table>
        </div>
    </div>

    @include('users.modal')
@endsection

@section('css')
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.20/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap4.min.css">
    <!-- Toastr -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">
    <!-- AlertifyJS -->
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/alertify.min.css"/>
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/themes/default.min.css"/>
@endsection

@section('scripts')
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.20/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap4.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <!-- AlertifyJS -->
    <script src="//cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/alertify.min.js"></script>
    <script>
        $(function () {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            //DataTables
            var table = $('#users-table').DataTable({
                processing: true,
                serverSide: true,
                scrollX: true,
                language: {
                    url: "{{ asset('datatable_spanish.json') }}"
                },
                ajax: '{!! route('getUsers') !!}',
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'name', name: 'name' },
                    { data: 'username', name: 'username' },
                    { data: 'email', name: 'email' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'actions', name: 'actions' },
                ]
            });

            //Modal Form
            let userModal = $('.user-modal');
            let form = userModal.find('form');
            let formLastAction = form.attr('action');
            let formLastMethod = form.attr('method');

            //Event when the table is draw
            table.on('draw', function () {
                //Delete User
                $('button.delete').on('click', function () {
                    let url = $(this).data('url');

                    alertify.confirm('Confirmacion','¿Estas seguro que desea eliminar el usuario?',
                        function () {
                            $.ajax({
                                url: url,
                                method: 'DELETE',
                                success: function () {
                                    //Reset the table with the new data
                                    table.ajax.reload();

                                    toastr.success('Usuario eliminado correctamente.');
                                },
                                error: function (response) {
                                    let error = response.responseJSON.error;
                                    toastr.error(error);
                                }
                            });
                        },
                        function () {
                            toastr.error('Cancelado');
                        }
                    ).set('labels', {ok: 'Si', cancel:"Cancelar"});
                });

                //Edit User
                $('button.edit').on('click', function () {
                    let url = $(this).data('url');

                    $.get(url, function () {
                        userModal.modal('show');
                    }).done(function (response) {
                        let user = response.user;

                        form.attr('action', "{{ url('users') }}/"+user.id);
                        form.attr('method', 'PUT');
                        form.find('#name').val(user.name);
                        form.find('#username').val(user.username);
                        form.find('#email').val(user.email);
                        form.find('#password').val('');
                        if (user.role === 'admin')
                            form.find('#role1').prop('checked', true);
                        if (user.role === 'user')
                            form.find('#role2').prop('checked', true);
                    }).fail(function (response) {
                        let error = response.responseJSON.error;
                        toastr.error(error);
                    });
                });
            });

            //Submit form to save user data
            form.submit(function (e) {
                e.preventDefault();

                let formUrl = form.attr('action');
                let formMethod = form.attr('method');
                let btnSubmit = form.find('.btn-primary');
                let btnSubmitValue = btnSubmit.text();

                resetErrorsFeedback(form);

                $.ajax({
                    method: formMethod,
                    url: formUrl,
                    data: form.serialize(),
                    beforeSend: function(){
                        btnSubmit.html('<i class="fas fa-spinner fa-spin"></i>');
                    },
                    success: function (response) {
                        //Restore the button value
                        btnSubmit.html(btnSubmitValue);
                        //Hide the modal
                        userModal.modal('hide');
                        //Reset the table with the new data
                        table.ajax.reload();

                        toastr.success('Usuario creado correctamente.');
                    },
                    error: function (response) {
                        let errors = response.responseJSON.errors;

                        //Restore the button value
                        btnSubmit.html(btnSubmitValue);

                        //Set name errors messages
                        if (errors.name){
                            let inputName = form.find('#name');
                            let feedback = inputName.parent().find('.invalid-feedback');

                            inputName.addClass('is-invalid');
                            feedback.html(errors.name);
                            feedback.removeClass('d-none');
                        }

                        //Set username errors messages
                        if (errors.username){
                            let inputName = form.find('#username');
                            let feedback = inputName.parent().find('.invalid-feedback');

                            inputName.addClass('is-invalid');
                            feedback.html(errors.username);
                            feedback.removeClass('d-none');
                        }

                        //Set email errors messages
                        if (errors.email){
                            let inputName = form.find('#email');
                            let feedback = inputName.parent().find('.invalid-feedback');

                            inputName.addClass('is-invalid');
                            feedback.html(errors.email);
                            feedback.removeClass('d-none');
                        }

                        //Set password errors messages
                        if (errors.password){
                            let inputName = form.find('#password');
                            let feedback = inputName.parent().find('.invalid-feedback');

                            inputName.addClass('is-invalid');
                            feedback.html(errors.password);
                            feedback.removeClass('d-none');
                        }

                    }
                });
            });

            //Reset errors feedback
            function resetErrorsFeedback(form){
                let feedbacks = form.find('.invalid-feedback');
                let inputsInvalid = form.find('input');

                feedbacks.addClass('d-none');
                inputsInvalid.removeClass('is-invalid');
            }

            //Modals Events
            userModal.on('hidden.bs.modal', function (e) {
                form[0].reset();
                form.attr('action', formLastAction);
                form.attr('method', formLastMethod);
                resetErrorsFeedback(form);
            });
        })
    </script>
@endsection
