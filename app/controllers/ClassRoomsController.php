<?php

class ClassRoomsController extends \BaseController {

	var $data = array();
	var $panelInit ;
	var $layout = 'dashboard';

	public function __construct(){
		$this->panelInit = new \DashboardInit();
		$this->data['panelInit'] = $this->panelInit;
		$this->data['breadcrumb']['Settings'] = \URL::to('/dashboard/languages');
		$this->data['users'] = \Auth::user();
		if($this->data['users']->role != "admin" AND $this->data['users']->role != "teacher") exit;

		if(!$this->data['users']->hasThePerm('classrooms')){
			exit;
		}
	}

	public function listAll()
	{
		$toReturn = array();
		$teachers = User::where('role','teacher')->get()->toArray();
        $toReturn['students'] = User::where('role', 'student')->get()->toArray();
		$toReturn['dormitory'] =  dormitories::get()->toArray();

		$toReturn['subjects'] = array();
		$subjects =  subject::get();
		foreach ($subjects as $value) {
		    $toReturn['subject'][$value->id] = $value->subjectTitle;
		}

		$toReturn['classes'] = array();
		$classes = \DB::table('classes')
					->leftJoin('dormitories', 'dormitories.id', '=', 'classes.dormitoryId')
					->select('classes.id as id',
					'classes.className as className',
					'classes.classTeacher as classTeacher',
					'classes.classSubjects as classSubjects',
					'dormitories.id as dormitory',
					'dormitories.dormitory as dormitoryName');

		if($this->data['users']->role == "teacher"){
			$classes = $classes->where('classTeacher','LIKE','%"'.$this->data['users']->id.'"%');
		}

		$classes = $classes->where('classAcademicYear',$this->panelInit->selectAcYear)->get();

		$toReturn['teachers'] = array();
		while (list($teacherKey, $teacherValue) = each($teachers)) {
			$toReturn['teachers'][$teacherValue['id']] = $teacherValue;
		}

		while (list($key, $class) = each($classes)) {
			$toReturn['classes'][$key] = $class;
            $toReturn['classes'][$key]->classSubjects = subject::whereIn('id',json_decode($toReturn['classes'][$key]->classSubjects))->get()->toArray();
            $toReturn['classes'][$key]->classStudents = User::where('role', 'student')->where('studentClass', $class->id)->get()->toArray();
            $toReturn['classes'][$key]->classTeachers = User::where('role', 'teacher')->whereIn('id', json_decode($toReturn['classes'][$key]->classTeacher))->get()->toArray();
            $toReturn['classes'][$key]->classDormitory = dormitories::where('id', $toReturn['classes'][$key]->dormitory)->get()->toArray();
		}

		return $toReturn;
	}

	public function delete($id){
		if($this->data['users']->role != "admin") exit;

		if ( $postDelete = classes::where('id', $id)->first() )
        {
            $postDelete->delete();
            return $this->panelInit->apiOutput(true,$this->panelInit->language['delClass'],$this->panelInit->language['classDeleted']);
        }else{
            return $this->panelInit->apiOutput(false,$this->panelInit->language['delClass'],$this->panelInit->language['classNotExist']);
        }
	}

	public function create(){
		if($this->data['users']->role != "admin") exit;

		$classes = new classes();
		$classes->className = Input::get('className');
		$classes->classTeacher = json_encode(Input::get('classTeacher'));
		$classes->classAcademicYear = $this->panelInit->selectAcYear;
		$classes->classSubjects = json_encode(Input::get('classSubjects'));
		if(Input::has('dormitoryId')){
			$classes->dormitoryId = Input::get('dormitoryId');
		}
		$classes->save();

		$classes->classTeacher = "";
		$teachersList = User::whereIn('id',Input::get('classTeacher'))->get();
		foreach ($teachersList as $teacher) {
			$classes->classTeacher .= $teacher->fullName.", ";
		}
		$classes->classSubjects = json_decode($classes->classSubjects);

		return $this->panelInit->apiOutput(true,$this->panelInit->language['addClass'],$this->panelInit->language['classCreated'],$classes->toArray() );
	}

	function fetch($id){
		$classDetail = classes::where('id',$id)->first()->toArray();
		$classDetail['classTeacher'] = json_decode($classDetail['classTeacher']);
		$classDetail['classSubjects'] = json_decode($classDetail['classSubjects']);
		return $classDetail;
	}

	function edit($id){
		if($this->data['users']->role != "admin") exit;

		$classes = classes::find($id);
		$classes->className = Input::get('className');
		$classes->classTeacher = json_encode(Input::get('classTeacher'));
		$classes->classSubjects = json_encode(Input::get('classSubjects'));
		if(Input::has('dormitoryId')){
			$classes->dormitoryId = Input::get('dormitoryId');
		}
		$classes->save();

		$classes->classTeacher = "";
		$teachersList = User::whereIn('id',Input::get('classTeacher'))->get();
		foreach ($teachersList as $teacher) {
			$classes->classTeacher .= $teacher->fullName.", ";
		}
		$classes->classSubjects = json_decode($classes->classSubjects);

		return $this->panelInit->apiOutput(true,$this->panelInit->language['editClass'],$this->panelInit->language['classUpdated'],$classes->toArray() );
	}

}
