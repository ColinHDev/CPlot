<?php

namespace ColinHDev\CPlot\math;

class CoordinateUtils {

    public static function getCoordinateFromChunk(int $chunkCoordinate, int $coordinateInChunk) : int {
        return $chunkCoordinate * 16 + $coordinateInChunk;
    }

    public static function getRasterCoordinate(int $coordinate, int $totalSize) : int {
        return $coordinate - (floor($coordinate / $totalSize) * $totalSize);
    }

    public static function isRasterPositionOnBorder(int $x, int $z, int $sizeRoad) : bool {
        if ($x === 0) {
            if ($z === 0) return true;
            if ($z >= ($sizeRoad - 1)) return true;

        } else if ($x === ($sizeRoad - 1)) {
            if ($z === 0) return true;
            if ($z >= ($sizeRoad - 1)) return true;
        }

        if ($z === 0) {
            if ($x === 0) return true;
            if ($x >= ($sizeRoad - 1)) return true;

        } else if ($z === ($sizeRoad - 1)) {
            if ($x === 0) return true;
            if ($x >= ($sizeRoad - 1)) return true;
        }
        return false;
    }
}