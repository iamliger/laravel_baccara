<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Logic2Pattern;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class Logic2PatternController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $patterns = Logic2Pattern::paginate(10);
        return view('admin.logic2.index', compact('patterns'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.logic2.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:logic2_patterns,name'],
            'description' => ['nullable', 'string'],
            'sequence' => ['required', 'string'], // 폼에서는 문자열로 받음
            'is_active' => ['boolean'],
        ]);
        
        // 쉼표로 구분된 문자열을 정수 배열로 변환
        $validated['sequence'] = array_map('intval', explode(',', $validated['sequence']));
        $validated['is_active'] = $request->has('is_active');

        Logic2Pattern::create($validated);

        return redirect()->route('admin.logic2.index')->with('success', '새로운 Logic-2 패턴을 추가했습니다.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Logic2Pattern $logic2)
    {
        return view('admin.logic2.edit', ['pattern' => $logic2]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Logic2Pattern $logic2)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('logic2_patterns')->ignore($logic2->id)],
            'description' => ['nullable', 'string'],
            'sequence' => ['required', 'string'],
            'is_active' => ['boolean'],
        ]);

        $validated['sequence'] = array_map('intval', explode(',', $validated['sequence']));
        $validated['is_active'] = $request->has('is_active');
        
        $logic2->update($validated);

        return redirect()->route('admin.logic2.index')->with('success', 'Logic-2 패턴을 수정했습니다.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Logic2Pattern $logic2)
    {
        $logic2->delete();
        return redirect()->route('admin.logic2.index')->with('success', 'Logic-2 패턴을 삭제했습니다.');
    }
}
