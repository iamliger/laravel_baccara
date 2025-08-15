<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BaccaraConfig;
use Illuminate\Http\Request;

class Logic3PatternController extends Controller
{
    /**
     * Logic 3 설정 수정 폼을 보여줍니다.
     */
    public function edit()
    {
        // bc_id가 1인 설정을 찾거나, 없으면 새로 만듭니다.
        $config = BaccaraConfig::firstOrCreate(['bc_id' => 1]);

        return view('admin.logic3.edit', compact('config'));
    }

    /**
     * Logic 3 설정을 업데이트합니다.
     */
    public function update(Request $request)
    {
        // 1. 유효성 검사
        $validated = $request->validate([
            'pattern_count' => ['required', 'integer', 'min:1', 'max:50'],
            'sequences' => ['required', 'array'],
            'sequences.*' => ['required', 'array', 'size:7'], // 각 시퀀스는 7개의 아이템을 가져야 함
            'sequences.*.*' => ['required', 'in:1,-1'], // 각 아이템은 1 또는 -1 이어야 함
        ]);
        
        // 2. JSON 데이터 생성
        $config_data = [
            'pattern_count' => $validated['pattern_count'],
            'sequences' => $validated['sequences']
        ];
        
        // 3. DB 업데이트
        $config = BaccaraConfig::firstOrCreate(['bc_id' => 1]);
        $config->update([
            'logic3_patterns' => $config_data
        ]);

        return redirect()->route('admin.logic3.edit')->with('success', 'Logic-3 패턴이 성공적으로 저장되었습니다.');
    }
}
