<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;

class CompanyController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return LengthAwarePaginator
     */
    public function index(Request $request)
    {
        return Company::paginate($request->limit, ['*'], 'page', $request->page);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:company,email',
            'website' => 'nullable|url',
            'logo' => 'nullable|image|mimes:png',
        ])->validate();

        try {
            $newCompany = new Company;
            if ($request->hasFile('logo')) {
                $logo = $request->file('logo');
                $filename = time() . '_' . $logo->getClientOriginalName();
                Image::make($logo)->resize(100, 100)->save(storage_path('/app/public/' . $filename));
                $newCompany->logo = $filename;
            };

            $newCompany->name = $request->name;
            $newCompany->email = $request->email;
            $newCompany->website = $request->website;
            $newCompany->save();

            return response()->json([
                'message' => 'A company has been created successfully'
            ], 201);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ], $exception->getStatusCode());
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        Validator::make($request->all(), [
            'id' => 'required|int',
            'name' => 'required|string',
            'email' => 'required|email|unique:company,email,'. $request->id,
            'website' => 'nullable|url',
            'logo' => 'nullable|image|mimes:png',
        ])->validate();

        try {
            $editCompany = Company::find($request->id);
            $oldLogoPath = $editCompany->logo;
            if (!$editCompany) {
                return response()->json([
                    'message' => 'The selected company cannot be found - update has failed!'
                ], 422);
            }

            if ($request->hasFile('logo')) {
                $logo = $request->file('logo');
                $filename = time() . '_' . $logo->getClientOriginalName();
                Image::make($logo)->resize(100, 100)->save(storage_path('/app/public/' . $filename));
                $editCompany->logo = $filename;
            };

            $editCompany->name = $request->name;
            $editCompany->email = $request->email;
            $editCompany->website = $request->website;
            $editCompany->save();

            // Remove old logo if new logo has been saved
            if ($request->hasFile('logo')) {
                if (Storage::exists('/public/' . $oldLogoPath)) {
                    Storage::delete('/public/' . $oldLogoPath);
                }
            }

            return response()->json([
                'message' => 'The selected company has been updated successfully'
            ], 201);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ], $exception->getStatusCode());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        Validator::make($request->all(), [
            'id' => 'required|int',
        ])->validate();

        try {
            $companyId = $request->get('id');
            Employee::where('company_id', '=', $companyId)->delete();
            Company::findOrFail($companyId)->delete();
            return response()->json([
                'message' => 'The selected company has been deleted'
            ], 200);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ], $exception->getStatusCode());
        }

    }
}
