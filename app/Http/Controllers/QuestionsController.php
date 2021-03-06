<?php

namespace App\Http\Controllers;

use App\Question;
use App\Questionnaire;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class QuestionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $questions = Question::all();

        return view('questions.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $messages = [
            'question.required'             =>  'La Pregunta es requerida.',
            'answer.required'               =>  'La Respuesta es requerida.',
            'questionnaire_id.required'     =>  'El Cuestionario es requerido.',
        ];

        $validator = Validator::make($request->all(), [
            'question'          =>  'required',
            'answer'            =>  'required',
            'questionnaire_id'  =>  'required',
        ], $messages);

        if ($validator->fails())
            return response()->json(['success' => false, 'errors' => $validator->errors()], 409);

        $question = new Question($request->all());
        $question->save();

        return response()->json(['success' => true, 'question' => $question], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $question = Question::find($id);

        if(!$question)
            return response()->json(['success' => false, 'error' => 'La Pregunta no existe.'], 404);

        return response()->json(['success' => true, 'question' => $question], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $question = Question::find($id);

        if(!$question)
            return response()->json(['success' => false, 'error' => 'La Pregunta no existe.'], 404);

        $messages = [
            'question.required'             =>  'La Pregunta es requerida.',
            'answer.required'               =>  'La Respuesta es requerida.',
            'questionnaire_id.required'     =>  'El Cuestionario es requerido.',
        ];

        $validator = Validator::make($request->all(), [
            'question'          =>  'required',
            'answer'            =>  'required',
            'questionnaire_id'  =>  'required',
        ], $messages);

        if ($validator->fails())
            return response()->json(['success' => false, 'errors' => $validator->errors()], 409);

        $question->questionnaire_id = $request->questionnaire_id;
        $question->answer = $request->answer;
        $question->question = $request->question;
        $question->help = $request->help;
        $question->save();

        return response()->json(['success' => true, 'question' => $question], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $question = Question::find($id);

        if(!$question)
            return response()->json(['success' => false, 'error' => 'La Pregunta no existe.'], 404);

        $question->delete();

        return response()->json(['success' => true, 'message' => 'Pregunta eliminada correctamente.'], 200);
    }

    //Get Question to draw DataTables
    public function getQuestionsDataTable(Request $request)
    {
        $questions = Question::where('questionnaire_id','=', $request->questionnaire_id)->get();
        $datatables = Datatables::of($questions)
            ->editColumn('actions', function ($question){
                $output = "<button class='btn btn-success btn-sm edit' data-id='".$question->id."' data-url='".route('questions.edit',$question->id)."'><i class='fas fa-edit'></i></button> ";
                $output .= "<button class='btn btn-danger btn-sm delete' data-id='".$question->id."' data-url='".route('questions.destroy',$question->id)."'><i class='fas fa-trash'></i></button>";

                return $output;
            })->rawColumns(['actions']);

        return $datatables->make(true);
    }

    //Check Question/Answer
    public function checkAnswer(Request $request, $id)
    {
        $question = Question::find($id);

        if ($question->answer == $request->answer)
            return response()->json(['check' => true], 200);

        return response()->json(['check' => false], 200);
    }

    //Get Help
    public function getHelp($id)
    {
        $user = auth()->user();
        $question = Question::find($id);

        return response()->json(['help' => $question->help], 200);
    }
}
