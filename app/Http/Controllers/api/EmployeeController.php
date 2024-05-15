<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{

    public function getEmployee($config)
    {
        $where = (array_key_exists('where', $config)) ? $config['where'] : null;
        $pagination_itemQuantity = (array_key_exists('pagination_itemQuantity', $config)) ? $config['pagination_itemQuantity'] : 0;
        $pagination_step = (array_key_exists('pagination_step', $config)) ? $config['pagination_step'] : 0;

        $search = DB::table('employees');

        if ($where) {
            $search = $search->where($where);
        }
        $search->select(
            'employees.id as id',
            'employees.lastname as lastname',
            'employees.second_lastname as second_lastname',
            'employees.first_name as first_name',
            'employees.other_names as other_names',
            'employees.country_employment as country_employment',
            'employees.type_identification as type_identification',
            'employees.code_identification as code_identification',
            'employees.email as email',
            'employees.date_admission as date_admission',
            'employees.area as area',
            'employees.state as state',

            'employees.created_at as created_at',
            'employees.updated_at as updated_at',
        );
        if ($pagination_itemQuantity) {
            $search = $search->paginate($pagination_itemQuantity, null, 'page', $pagination_step);
            $search = $search;
        } else {
            $search = $search->get();
        }

        return $search;
    }

    public function search(Request $request)
    {
        $where = ($request->where) ? json_decode($request->where, true) : null;
        $pagination_itemQuantity = ($request->pagination_itemQuantity && $request->pagination_itemQuantity !== 'undefined') ? $request->pagination_itemQuantity : 0;
        $pagination_step = ($request->pagination_step && $request->pagination_step !== 'undefined') ? $request->pagination_step : 0;

        $arrayConfig = [
            'where' => $where,
            'pagination_itemQuantity' => $pagination_itemQuantity,
            'pagination_step' => $pagination_step,
        ];

        $employees = $this->getEmployee($arrayConfig);

        return response()->json([
            'status' => true,
            'employees' => $employees,
        ]);
    }

    public function validateCountryEmployment($country_employment)
    {
        if ($country_employment === 'COL' || $country_employment === 'EEUU') {
            return true;
        }
        return false;
    }

    public function validateTypeIdentification($type_identification)
    {
        if ($type_identification === 'cedula_ciudadania' || $type_identification === 'cedula_extranjeria' || $type_identification === 'pasaporte' || $type_identification === 'permiso_especial') {
            return true;
        }
        return false;
    }

    public function validateArea($area)
    {
        if (
            $area === 'administracion' || $area === 'financiera' || $area === 'compras'
            || $area === 'infraestructura' || $area === 'operacion' || $area === 'talento_humano'
            || $area === 'servicios_varios'
        ) {
            return true;
        }
        return false;
    }

    public function validateCodeIdentification($code_identification)
    {
        $arrayConfigSearchIdentificationInDb = [
            'where' => [['employees.code_identification', '=', $code_identification]],
            'pagination_itemQuantity' => 0,
            'pagination_step' => 0,
        ];

        $codeIdentificationInDb = $this->getEmployee($arrayConfigSearchIdentificationInDb);
        if (count($codeIdentificationInDb)) {
            return [
                'status' => false,
                'error' => 'El campo otros nombres "code_identification" ya se encuentra registrado.',
            ];
        }
        return [
            'status' => true,
        ];
    }

    public function validateInput($request)
    {
        if (!preg_match('/^[A-Z \d]{0,20}+$/', $request->lastname)) {
            return [
                'status' => false,
                'error' => 'El apellido paterno "lastname" es incorrecto.',
            ];
        }

        if (!preg_match('/^[A-Z \d]{0,20}+$/', $request->second_lastname)) {
            return [
                'status' => false,
                'error' => 'El apellido materno "second_lastname" es incorrecto.',
            ];
        }

        if (!preg_match('/^[A-Z \d]{0,20}+$/', $request->first_name)) {
            return [
                'status' => false,
                'error' => 'El nombre "first_name" es incorrecto.',
            ];
        }

        if (!preg_match('/^[A-Z \d]{0,50}+$/', $request->other_names)) {
            return [
                'status' => false,
                'error' => 'El campo otros nombres "other_names" es incorrecto.',
            ];
        }

        if (!$this->validateCountryEmployment($request->country_employment)) {
            return [
                'status' => false,
                'error' => 'El pais de empleo "country_employment" es incorrecto.',
            ];
        }

        if (!$this->validateTypeIdentification($request->type_identification)) {
            return [
                'status' => false,
                'error' => 'El tipo de identificación "type_identification" es incorrecto.',
            ];
        }

        if (!preg_match('/^[A-Za-z0-9\d]{0,20}+$/', $request->code_identification)) {
            return [
                'status' => false,
                'error' => 'El campo otros nombres "code_identification" es incorrecto.',
            ];
        }

        if (!$this->validateArea($request->area)) {
            return [
                'status' => false,
                'error' => 'El area "area" es incorrecto.',
            ];
        }
        return [
            'status' => true,
        ];
    }

    public function add(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'lastname' => 'required',
            'second_lastname' => 'required',
            'first_name' => 'required',
            'country_employment' => 'required',
            'type_identification' => 'required',
            'code_identification' => 'required',
            'date_admission' => 'required',
            'area' => 'required',
        ]);

        if ($validated->fails()) {
            $error = $validated->errors()->first();
            return response()->json([
                'status' => false,
                'error' => $error,
            ]);
        }

        if (!$this->validateInput($request)['status']) {
            return response()->json($this->validateInput($request));
        }

        $lastname = $request->lastname;
        $second_lastname = $request->second_lastname;
        $first_name = $request->first_name;
        $other_names = $request->other_names;
        $country_employment = $request->country_employment;
        $type_identification = $request->type_identification;
        $code_identification = $request->code_identification;
        $date_admission = $request->date_admission;
        $area = $request->area;

        $domain = $country_employment === 'COL' ? 'global.com.co' : 'global.com.us';
        $email = strtolower(str_replace(' ', '', $first_name . '.' . $lastname . '@' . $domain));

        if (!$this->validateCodeIdentification($code_identification)['status']) {
            return response()->json($this->validateCodeIdentification($code_identification));
        }

        $arrayConfigSearch = [
            'where' => [['employees.email', '=', $email]],
            'pagination_itemQuantity' => 0,
            'pagination_step' => 0,
        ];

        $emailInDb = $this->getEmployee($arrayConfigSearch);
        if (count($emailInDb)) {
            $email = strtolower(str_replace(' ', '', $first_name . '.' . $lastname . '.' . uniqid() . '@' . $domain));
        }

        $dataToAdd = [
            'lastname' => $lastname,
            'second_lastname' => $second_lastname,
            'first_name' => $first_name,
            'other_names' => $other_names,
            'country_employment' => $country_employment,
            'type_identification' => $type_identification,
            'code_identification' => $code_identification,
            'email' => $email,
            'date_admission' => $date_admission,
            'area' => $area,
            'state' => true,
        ];

        try {
            $post = Employee::create($dataToAdd);
            return response()->json([
                'status' => true,
                'post' => $post,
                'message' => 'Se guardó de manera exitosa',
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Ocurrió un error al guardar',
            ]);
        }
    }

    public function update(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id' => 'required',
            'lastname' => 'required',
            'second_lastname' => 'required',
            'first_name' => 'required',
            'country_employment' => 'required',
            'type_identification' => 'required',
            'code_identification' => 'required',
            'date_admission' => 'required',
            'area' => 'required',
        ]);

        if ($validated->fails()) {
            $error = $validated->errors()->first();
            return response()->json([
                'status' => false,
                'error' => $error,
            ]);
        }

        if (!$this->validateInput($request)['status']) {
            return response()->json($this->validateInput($request));
        }

        $id = $request->id;
        $lastname = $request->lastname;
        $second_lastname = $request->second_lastname;
        $first_name = $request->first_name;
        $other_names = $request->other_names;
        $country_employment = $request->country_employment;
        $type_identification = $request->type_identification;
        $code_identification = $request->code_identification;
        $date_admission = $request->date_admission;
        $area = $request->area;

        $domain = $country_employment === 'COL' ? 'global.com.co' : 'global.com.us';
        $email = strtolower(str_replace(' ', '', $first_name . '.' . $lastname . '@' . $domain));

        // if (!$this->validateCodeIdentification($code_identification)['status']) {
        //     return response()->json($this->validateCodeIdentification($code_identification));
        // }

        $arrayConfigSearch = [
            'where' => [['employees.email', '=', $email]],
            'pagination_itemQuantity' => 0,
            'pagination_step' => 0,
        ];

        $emailInDb = $this->getEmployee($arrayConfigSearch);
        if (count($emailInDb)) {
            $email = strtolower(str_replace(' ', '', $first_name . '.' . $lastname . '.' . uniqid() . '@' . $domain));
        }

        $dataToAdd = [
            'lastname' => $lastname,
            'second_lastname' => $second_lastname,
            'first_name' => $first_name,
            'other_names' => $other_names,
            'country_employment' => $country_employment,
            'type_identification' => $type_identification,
            'code_identification' => $code_identification,
            'email' => $email,
            'date_admission' => $date_admission,
            'area' => $area,
            'state' => true,
        ];

        try {
            $post = Employee::where('id', $id)
                ->update($dataToAdd);
            return response()->json([
                'status' => true,
                'post' => $post,
                'message' => 'Se guardó de manera exitosa',
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Ocurrió un error al guardar',
            ]);
        }
    }

    public function delete(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validated->fails()) {
            // validation failed
            $error = $validated->errors()->first();

            return response()->json([
                'status' => false,
                'error' => $error,
            ]);
        }
        // validation passed
        $id = $request->id;
        $deleted = Employee::where('id', $id)->delete();

        return response()->json([
            'status' => true,
            'employeeDeleted' => $deleted,
            'id' => $id,
        ]);
    }
}
