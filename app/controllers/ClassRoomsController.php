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

		$toReturn['classrooms'] = array();
		$classrooms = classrooms::get()->toArray();
        while (list($key, $classroom) = each($classrooms)) {
            $classroom['associatedClasses'] = classes::where('id', $classroom['classes'])->get()->toArray();
            $classroom['associatedSubjects'] = subject::whereIn('id',json_decode($classroom['subjects']))->get()->toArray();
            $classroom['associatedStudents'] = User::whereIn('id',json_decode($classroom['students']))->get()->toArray();
            $classroom['associatedTeachers'] = User::whereIn('id',json_decode($classroom['teachers']))->get()->toArray();
            $classroom['associatedDormitory'] = dormitories::where('id', $classroom['dormitory'])->get()->toArray();
            $toReturn['classrooms'][$key] = $classroom;
        }

		$toReturn['dormitory'] =  dormitories::get()->toArray();

		$toReturn['classes'] = array();
		$classes = classes::get()->toArray();
		while (list($key, $class) = each($classes)) {
            $class['classSubjects'] = subject::whereIn('id',json_decode($class['classSubjects']))->get()->toArray();
            $class['classStudents'] = User::where('role', 'student')->where('studentClass', $class['id'])->get()->toArray();
            $class['classTeachers'] = User::where('role', 'teacher')->whereIn('id', json_decode($class['classTeacher']))->get()->toArray();
            $class['classDormitory'] = dormitories::where('id', $class['dormitoryId'])->get()->toArray();
            $toReturn['classes'][$key] = $class;
		}

		return $toReturn;
	}

	public function delete($id){
		if($this->data['users']->role != "admin") exit;

		if ( $postDelete = classrooms::where('id', $id)->first() )
        {
            $postDelete->delete();
            return $this->panelInit->apiOutput(true,$this->panelInit->language['delClassRoom'],$this->panelInit->language['classRoomDeleted']);
        }else{
            return $this->panelInit->apiOutput(false,$this->panelInit->language['delClassRoom'],$this->panelInit->language['classRoomNotExist']);
        }
	}

	public function create(){
		if($this->data['users']->role != "admin") exit;

		$classrooms = new classrooms();

		$classrooms->classRoomName = Input::get('classRoomName');
        $classrooms->classes = Input::get('class');
        $classrooms->subjects = json_encode(Input::get('subjects'));
        $classrooms->students = json_encode(Input::get('students'));
        $classrooms->teachers = json_encode(Input::get('teachers'));

		if(Input::has('dormitory')){
            $classrooms->dormitory = Input::get('dormitory');
		}
        $classrooms->save();

		$return = $classrooms->toArray();
        $return['associatedClasses'] = classes::where('id', $classrooms['classes'])->get()->toArray();
        $return['associatedSubjects'] = subject::whereIn('id',json_decode($classrooms['subjects']))->get()->toArray();
        $return['associatedStudents'] = User::whereIn('id',json_decode($classrooms['students']))->get()->toArray();
        $return['associatedTeachers'] = User::whereIn('id',json_decode($classrooms['teachers']))->get()->toArray();
        $return['associatedDormitory'] = dormitories::where('id', $classrooms['dormitory'])->get()->toArray();

		return $this->panelInit->apiOutput(true,$this->panelInit->language['addClassRoom'],$this->panelInit->language['classRoomCreated'],  $return);
	}

	function fetch($id){
		$classrooms = classrooms::where('id',$id)->first()->toArray();
        $classrooms['associatedClasses'] = classes::where('id', $classrooms['classes'])->get()->toArray();
        $classrooms['associatedSubjects'] = subject::whereIn('id',json_decode($classrooms['subjects']))->get()->toArray();
        $classrooms['associatedStudents'] = User::whereIn('id',json_decode($classrooms['students']))->get()->toArray();
        $classrooms['associatedTeachers'] = User::whereIn('id',json_decode($classrooms['teachers']))->get()->toArray();
        $classrooms['associatedDormitory'] = dormitories::where('id', $classrooms['dormitory'])->get()->toArray();
        $classrooms['subjects'] = json_decode($classrooms['subjects']);
        $classrooms['students'] = json_decode($classrooms['students']);
        $classrooms['teachers'] = json_decode($classrooms['teachers']);
		return $classrooms;
	}

	function edit($id){
		if($this->data['users']->role != "admin") exit;

		$classrooms = classrooms::find($id);

        $classrooms->classRoomName = Input::get('classRoomName');
        $classrooms->classes = Input::get('classes');
        $classrooms->subjects = json_encode(Input::get('subjects'));
        $classrooms->students = json_encode(Input::get('students'));
        $classrooms->teachers = json_encode(Input::get('teachers'));

        if(Input::has('dormitory')){
            $classrooms->dormitory = Input::get('dormitory');
        }
        $classrooms->save();

        $return = $classrooms->toArray();
        $return['associatedClasses'] = classes::where('id', $classrooms['classes'])->get()->toArray();
        $return['associatedSubjects'] = subject::whereIn('id',json_decode($classrooms['subjects']))->get()->toArray();
        $return['associatedStudents'] = User::whereIn('id',json_decode($classrooms['students']))->get()->toArray();
        $return['associatedTeachers'] = User::whereIn('id',json_decode($classrooms['teachers']))->get()->toArray();
        $return['associatedDormitory'] = dormitories::where('id', $classrooms['dormitory'])->get()->toArray();

		return $this->panelInit->apiOutput(true,$this->panelInit->language['editClassRoom'],$this->panelInit->language['classRoomUpdated'], $return);
	}

}
