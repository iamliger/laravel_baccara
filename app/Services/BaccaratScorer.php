<?php

namespace App\Services;

class BaccaratScorer
{
    private array $matrix = [];
    private int $lastCol = 0;

    public function getAllRoads(string $jokbo): array
    {
        $this->buildMatrix($jokbo);
        return [
            'main' => $this->getMainRoadData(),
            'big_eye' => $this->getDerivedRoadData(2, 3), // 3매
            'small' => $this->getDerivedRoadData(3, 4),   // 4매
            'cockroach' => $this->getDerivedRoadData(4, 5), // 5매
            'sixth' => $this->getDerivedRoadData(5, 6)    // 6매
        ];
    }
    
    private function buildMatrix(string $jokbo): void
    {
        $this->matrix = []; $this->lastCol = 0;
        if (empty($jokbo)) return;
        $col = 1; $row = 1; $last = null;
        foreach (str_split($jokbo) as $char) {
            if ($last !== null && $char !== $last) { $col++; $row = 1; }
            if ($row > 6) {
                $this->matrix[$col + ($row - 7)][5] = $char;
            } else {
                $this->matrix[$col][$row - 1] = $char;
            }
            $row++; $last = $char;
        }
        $this->lastCol = $col;
    }

    private function getMainRoadData(): array
    {
        $data = [];
        foreach ($this->matrix as $col => $rows) {
            foreach ($rows as $row => $type) {
                $data[] = ['col' => $col, 'row' => $row + 1, 'type' => $type];
            }
        }
        return $data;
    }

    private function getDerivedRoadData(int $colOffset, int $rowNum): array
    {
        $road = []; $roadCol = 1; $roadRow = 1; $lastColor = null;
        for ($col = $colOffset; $col <= $this->lastCol + 5; $col++) {
            for ($row = 2; $row <= 7; $row++) {
                $refCol = $col - ($colOffset - 1);
                $cell1 = $this->getCell($refCol, $row);
                $cell2 = $this->getCell($col, $row);
                if ($row === 2) {
                    $cell2 = $this->getCell($col - 1, $row);
                }
                if ($cell1 === null && $cell2 === null) {
                    if ($row === 2 && $col > $this->lastCol) {
                        return $road;
                    }
                    continue;
                }
                $color = ($cell1 === $cell2) ? 'red' : 'blue';
                if ($lastColor !== null && $color !== $lastColor && !empty($road)) {
                    $roadCol++;
                    $roadRow = 1;
                }
                if ($roadRow > $rowNum - 1) {
                    $roadCol++;
                    $roadRow = 1;
                }
                $road[] = ['col' => $roadCol, 'row' => $roadRow, 'color' => $color];
                $roadRow++;
                $lastColor = $color;
            }
        }
        return $road;
    }
    
    private function getCell($col, $row)
    {
        return $this->matrix[$col][$row - 1] ?? null;
    }
}