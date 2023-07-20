<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Admin;
use App\Models\Domain;
use App\Models\Project;
use App\Models\Student;
use App\Models\Evaluator;
use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


class StudentController extends Controller
{

    public function dashboard()
    {
        $domain =Domain::where('status',1)->get();
        return view('submission',compact('domain'));
    }

    public function logout(){
        Auth::guard('student')->logout();
        return redirect('/');
    }

    public function save(Request $request){
        $incomingFields = $request->validate([
            'title'=> 'required',
            'abstract' =>'required',
            'keywords' => 'required',
            'domain'=>'required',
            'docType'=> 'required',
            'report'=>'required'
        ]);
        
        $incomingFields['student_id']=Auth::guard('student')->id();
        $filename = time()."__.".$request->file('report')->getClientOriginalExtension();
        $pathh= $request->file('report')->storeAs('public/uploads',$filename);
        $incomingFields['file_upload'] = $pathh;
        
        Project::create($incomingFields);
        return redirect('/student/dashboard')->with('success', 'Submitted Successfully!');
    }

    public function mySubmission()
    {
        $studentId = Auth::guard('student')->id();
        
        // Get projects that are not yet evaluated
        $notEvaluatedProjects = Project::where(function ($query) use ($studentId) {
            $query->whereDoesntHave('assignment')
                ->orWhereHas('assignment', function ($query) {
                    $query->where('status', '!=', 'admin_complete');
                });
        })
        ->where('student_id', $studentId)
        ->get();
        
        
        // Get projects that have been evaluated
        $evaluatedProjects = Project::Where('student_id', $studentId)
            ->whereHas('assignment', function ($query) {
                $query->where('status','admin_complete');
            })
            ->get();
        
        return view('mysubmission', compact('notEvaluatedProjects', 'evaluatedProjects'));
    }

}
