<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Rubix\ML\Classifiers\LogisticRegression;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Datasets\Unlabeled;
use Illuminate\Support\Facades\Log;

class AiPredictionController extends Controller
{
    public function predict(Request $request)
    {
        if (config('app.baccara_debug')) {
            Log::debug("==================================================");
            Log::debug("AI 예측 API 호출됨");
            Log::debug("==================================================");
        }

        // 1) 입력 검증
        $validated = $request->validate([
            'jokbo' => ['required', 'string', 'min:10'],
        ]);

        // 2) 전처리: T 제거(P/B만 사용)
        $jokbo = strtoupper($validated['jokbo']);
        $pb_jokbo = str_replace('T', '', $jokbo);
        $len = strlen($pb_jokbo);

        if (config('app.baccara_debug')) {
            Log::debug("[AI] 수신된 족보: {$jokbo} (순수 길ی: {$len})");
        }

        // 최소 길이 체크 (학습용 4 + 라벨 1 이상)
        if ($len < 5) {
            $msg = 'AI 모델을 학습시키기에 데이터가 부족합니다. (P/B만 ' . $len . '글자)';
            if (config('app.baccara_debug')) Log::debug("[AI] 오류: {$msg}");
            return response()->json(['success' => false, 'message' => $msg], 422);
        }

        // 3) 슬라이딩 윈도우로 학습 데이터 구성
        $samples = [];
        $labels  = [];

        for ($i = 0; $i <= $len - 5; $i++) {
            $feature_string = substr($pb_jokbo, $i, 4);     // 입력 4글자
            $label_char     = substr($pb_jokbo, $i + 4, 1); // 정답 1글자 ("P" 또는 "B")

            // 입력 벡터: P=1, B=0 의 단순 인코딩
            $feature_vector = [];
            foreach (str_split($feature_string) as $ch) {
                $feature_vector[] = ($ch === 'P') ? 1 : 0;
            }

            $samples[] = $feature_vector;
            $labels[]  = (string) $label_char; // 분류 라벨은 문자열로 강제
        }

        if (config('app.baccara_debug')) {
            Log::debug("[AI] 생성된 학습 샘플 개수: " . count($samples));
        }

        // 4) 라벨 다양성 체크(P/B 모두 포함 여부)
        $unique_labels = array_values(array_unique($labels));
        if (count($unique_labels) < 2) {
            $message = 'AI 모델 학습 불가: 학습 데이터에 P와 B가 모두 포함되어야 합니다.';
            if (config('app.baccara_debug')) Log::debug("[AI] 오류: " . $message);
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        try {
            // 5) 데이터셋 구성
            if (config('app.baccara_debug')) Log::debug("[AI] Rubix ML 데이터셋 생성 시도...");
            $dataset = Labeled::build($samples, $labels);
            if (config('app.baccara_debug')) Log::debug("[AI] Rubix ML 데이터셋 생성 성공!");

            // 6) 모델 학습
            if (config('app.baccara_debug')) Log::debug("[AI] 로지스틱 회귀 모델 생성 및 학습 시도...");
            $estimator = new LogisticRegression();
            $estimator->train($dataset);
            if (config('app.baccara_debug')) Log::debug("[AI] 모델 학습 성공!");

            // 7) 예측용 입력(마지막 4글자)
            if ($len < 4) {
                $msg = '예측을 위해 최소 4글자의 P/B 기록이 필요합니다.';
                if (config('app.baccara_debug')) Log::debug("[AI] 오류: {$msg}");
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            $last4 = substr($pb_jokbo, -4);
            $prediction_sample_array = [];
            foreach (str_split($last4) as $ch) {
                $prediction_sample_array[] = ($ch === 'P') ? 1 : 0;
            }

            // 8) 확률 예측 (배열 그대로 전달)
            if (config('app.baccara_debug')) Log::debug("[AI] 확률 예측 시도...");

            $prediction_dataset = new Unlabeled([$prediction_sample_array]);
            $probabilities = $estimator->proba($prediction_dataset);
            
            if (config('app.baccara_debug')) {
                Log::debug("[AI] 확률 예측 성공! 결과: " . json_encode($probabilities));
            }

            $first_prediction = $probabilities[0] ?? [];
            // 라벨 키는 "P","B" 문자열로 나옴
            $p_probability = $first_prediction['P'] ?? 0.0;
            $b_probability = $first_prediction['B'] ?? 0.0;

            $recommendation = ($p_probability > $b_probability) ? 'P' : 'B';
            $confidence = max($p_probability, $b_probability) * 100.0;

            if (config('app.baccara_debug')) {
                Log::debug("[AI] 최종 예측: {$recommendation}, 신뢰도: {$confidence}%");
            }

            return response()->json([
                'success'     => true,
                'recommend'   => $recommendation,
                'confidence'  => round($confidence, 1),
            ]);

        } catch (\Throwable $e) {
            if (config('app.baccara_debug')) {
                Log::error("[AI] 치명적 오류 발생!");
                Log::error(" - 오류 메시지: " . $e->getMessage());
                Log::error(" - 파일: " . $e->getFile() . " (Line: " . $e->getLine() . ")");
                Log::error(" - 스택 트레이스: \n" . $e->getTraceAsString());
            }

            return response()->json([
                'success' => false,
                'message' => 'AI 모델 학습 중 내부 서버 오류가 발생했습니다.',
            ], 500);
        }
    }
}
