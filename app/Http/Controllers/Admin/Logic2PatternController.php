<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BaccaraConfig;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class Logic2PatternController extends Controller
{
    /**
     * Logic 2 설정 수정 폼을 보여줍니다.
     */
    public function edit()
    {
        // bc_id가 1인 설정을 찾거나, 없으면 새로 만듭니다.
        $config = BaccaraConfig::firstOrCreate(['bc_id' => 1]);

        return view('admin.logic2.edit', compact('config'));
    }

    /**
     * Logic 2 설정을 업데이트합니다.
     */
    public function update(Request $request)
    {
        // 1. 유효성 검사
        $validated = $request->validate([
            'pattern_count' => ['required', 'integer', 'min:1', 'max:50'],
            'sequences' => ['required', 'array'],
            // 각 시퀀스는 최소 1개, 최대 10개의 아이템을 가질 수 있습니다.
            'sequences.*' => ['required', 'array', 'min:1', 'max:10'], 
            // 각 아이템은 '붙(1)' 또는 '꺽(-1)' 이어야 합니다.
            'sequences.*.*' => ['required', 'in:1,-1'], 
        ]);
        
        // 2. 데이터베이스에 저장할 JSON 데이터 생성
        $config_data = [
            'pattern_count' => $validated['pattern_count'],
            'sequences' => $validated['sequences']
        ];
        
        // 3. DB 업데이트
        $config = BaccaraConfig::firstOrCreate(['bc_id' => 1]);
        $config->update([
            'logic2_patterns' => $config_data // logic2_patterns 필드에 저장합니다.
        ]);

        return redirect()->route('admin.logic2.edit')->with('success', 'Logic-2 패턴이 성공적으로 저장되었습니다.');
    }
}
