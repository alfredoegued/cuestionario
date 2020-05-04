@extends('layouts.app')

@section('page_title', $questionnaire->name)

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Preguntas</h3>
                        <div class="card-tools">
                            <ul class="nav nav-pills ml-auto">
                                <li class="nav-item">
                                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#question-modal">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="questions-table" class="table table-striped nowrap" style="width: 100%;">
                            <thead>
                            <tr>
                                <td>ID</td>
                                <td>Pregunta</td>
                                <td>Acciones</td>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Respuesta</h3>
                        <div class="card-tools">
                            <ul class="nav nav-pills ml-auto">
                                <li class="nav-item">
                                    <button type="button" class="btn btn-primary btn-sm d-none" id="btn-answer-modal" data-toggle="modal" data-target="#answer-modal">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="answers-table" class="table table-striped nowrap" style="width: 100%;">
                            <thead>
                            <tr>
                                <td>ID</td>
                                <td>Respuesta</td>
                                <td>Acciones</td>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('questionnaires.modal_question')
    @include('questionnaires.modal_answer')
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

            /*
            * Questions Table
            * */
            var questionID = 0;

            //Init DataTables
            var tableQuestions = $('#questions-table').DataTable({
                processing: true,
                serverSide: true,
                info: false,
                searching: false,
                paging: false,
                language: {
                    url: "/datatable_spanish.json"
                },
                ajax: {
                    url: '{!! route('getQuestions') !!}',
                    data: {'questionnaire_id': '{{ $questionnaire->id }}'}
                },
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'question', name: 'question' },
                    { data: 'actions', name: 'actions' },
                ]
            });

            //Modal Form
            let questionModal = $('.question-modal');
            let formQuestion = questionModal.find('form');
            let formQuestionLastAction = formQuestion.attr('action');
            let formQuestionLastMethod = formQuestion.attr('method');

            //Event when the table is draw
            tableQuestions.on('draw', function () {
                //Delete Question
                $('#questions-table button.delete').on('click', function () {
                    let url = $(this).data('url');

                    alertify.confirm('Confirmacion','¿Estas seguro que desea eliminar la Pregunta?',
                        function () {
                            $.ajax({
                                url: url,
                                method: 'DELETE',
                                success: function () {
                                    //Reset the table with the new data
                                    tableQuestions.ajax.reload();

                                    toastr.success('Pregunta eliminada correctamente.');
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

                //Edit Question
                $('#questions-table button.edit').on('click', function () {
                    let url = $(this).data('url');

                    $.get(url, function () {
                        questionModal.modal('show');
                    }).done(function (response) {
                        let question = response.question;

                        formQuestion.attr('action', '/questions/'+question.id);
                        formQuestion.attr('method', 'PUT');
                        formQuestion.find('#question').val(question.question);
                    }).fail(function (response) {
                        let error = response.responseJSON.error;
                        toastr.error(error);
                    });
                });

                //Show Answers from a Question
                $('#questions-table button.show').on('click', function () {
                    drawAnswersTable($(this).data('id'));
                    questionID = $(this).data('id');
                });
            });

            //Submit form to save user data
            formQuestion.submit(function (e) {
                e.preventDefault();

                let formUrl = formQuestion.attr('action');
                let formMethod = formQuestion.attr('method');
                let btnSubmit = formQuestion.find('.btn-primary');
                let btnSubmitValue = btnSubmit.text();

                resetErrorsFeedback(formQuestion);

                $.ajax({
                    method: formMethod,
                    url: formUrl,
                    data: formQuestion.serialize(),
                    beforeSend: function(){
                        btnSubmit.html('<i class="fas fa-spinner fa-spin"></i>');
                    },
                    success: function (response) {
                        //Restore the button value
                        btnSubmit.html(btnSubmitValue);
                        //Hide the modal
                        questionModal.modal('hide');
                        //Reset the table with the new data
                        tableQuestions.ajax.reload();

                        toastr.success('Pregunta creada correctamente.');
                    },
                    error: function (response) {
                        let errors = response.responseJSON.errors;

                        //Restore the button value
                        btnSubmit.html(btnSubmitValue);

                        //Set question errors messages
                        if (errors.question){
                            let inputQuestion = formQuestion.find('#question');
                            let feedback = inputQuestion.parent().find('.invalid-feedback');

                            inputQuestion.addClass('is-invalid');
                            feedback.html(errors.question);
                            feedback.removeClass('d-none');
                        }
                    }
                });
            });

            //Reset errors feedback
            function resetErrorsFeedback(form){
                let feedbacks = form.find('.invalid-feedback');
                let inputsInvalid = form.find('input');
                let textareasInvalid = form.find('textarea');

                feedbacks.addClass('d-none');
                inputsInvalid.removeClass('is-invalid');
                textareasInvalid.removeClass('is-invalid');
            }

            //Modals Events
            questionModal.on('hidden.bs.modal', function (e) {
                formQuestion[0].reset();
                formQuestion.attr('action', formQuestionLastAction);
                formQuestion.attr('method', formQuestionLastMethod);
                resetErrorsFeedback(formQuestion);
            });

            /*
            * Answers Table
            * Separated in a function
            * */

            function drawAnswersTable(questionID) {
                //Init DataTables
                let tableAnswer = $('#answers-table');

                tableAnswer.DataTable().destroy();

                let tableAnswers = tableAnswer.DataTable({
                    processing: true,
                    serverSide: true,
                    info: false,
                    searching: false,
                    paging: false,
                    language: {
                        url: "/datatable_spanish.json"
                    },
                    ajax: {
                        url: '{!! route('getAnswers') !!}',
                        data: {'question_id': questionID}
                    },
                    columns: [
                        { data: 'id', name: 'id' },
                        { data: 'answer', name: 'answer' },
                        { data: 'actions', name: 'actions' },
                    ]
                });

                //Modal Form
                let answerModal = $('.answer-modal');
                let formAnswer = answerModal.find('form');
                let formAnswerLastAction = answerModal.attr('action');
                let formAnswerLastMethod = answerModal.attr('method');

                //Event when the table is draw
                tableAnswers.on('draw', function () {
                    //Enable/Disable Answer Modal Button
                    let answersEmpty = $('#answers-table tbody tr td.dataTables_empty');
                    let answerModalButton = $('#btn-answer-modal');

                    if (answersEmpty.length >= 1){
                        answerModalButton.removeClass('d-none');
                    } else {
                        answerModalButton.addClass('d-none');
                    }

                    //Delete Questionnaire
                    $('#answers-table button.delete').on('click', function () {
                        let url = $(this).data('url');

                        alertify.confirm('Confirmacion','¿Estas seguro que desea eliminar la Respuesta?',
                            function () {
                                $.ajax({
                                    url: url,
                                    method: 'DELETE',
                                    success: function () {
                                        //Reset the table with the new data
                                        tableAnswers.ajax.reload();

                                        toastr.success('Respuesta eliminada correctamente.');
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

                    //Edit Answer
                    $('#answers-table button.edit').on('click', function () {
                        let url = $(this).data('url');

                        $.get(url, function () {
                            answerModal.modal('show');
                        }).done(function (response) {
                            let answer = response.answer;

                            formAnswer.attr('action', '/answers/'+answer.id);
                            formAnswer.attr('method', 'PUT');
                            formAnswer.find('#answer').val(answer.answer);
                        }).fail(function (response) {
                            let error = response.responseJSON.error;
                            toastr.error(error);
                        });
                    });
                });

                //Submit form to save user data
                formAnswer.submit(function (e) {
                    e.preventDefault();

                    let formUrl = formAnswer.attr('action');
                    let formMethod = formAnswer.attr('method');
                    let btnSubmit = formAnswer.find('.btn-primary');
                    let btnSubmitValue = btnSubmit.text();

                    resetErrorsFeedback(formAnswer);

                    $.ajax({
                        method: formMethod,
                        url: formUrl,
                        data: formAnswer.serialize(),
                        beforeSend: function(){
                            btnSubmit.html('<i class="fas fa-spinner fa-spin"></i>');
                        },
                        success: function (response) {
                            //Restore the button value
                            btnSubmit.html(btnSubmitValue);
                            //Hide the modal
                            answerModal.modal('hide');
                            //Reset the table with the new data
                            tableAnswers.ajax.reload();

                            toastr.success('Respuesta creada correctamente.');
                        },
                        error: function (response) {
                            let errors = response.responseJSON.errors;

                            //Restore the button value
                            btnSubmit.html(btnSubmitValue);

                            //Set question errors messages
                            if (errors.answer){
                                let inputAnswer = formAnswer.find('#answer');
                                let feedback = inputAnswer.parent().find('.invalid-feedback');

                                inputAnswer.addClass('is-invalid');
                                feedback.html(errors.answer);
                                feedback.removeClass('d-none');
                            }
                        }
                    });
                });

                //Modals Events
                answerModal.on('hidden.bs.modal', function (e) {
                    formAnswer[0].reset();
                    formAnswer.attr('action', formQuestionLastAction);
                    formAnswer.attr('method', formQuestionLastMethod);
                    resetErrorsFeedback(formAnswer);
                });

                answerModal.on('shown.bs.modal', function () {
                    formAnswer.find('#question_id').val(questionID);
                });
            }

        });
    </script>
@endsection
