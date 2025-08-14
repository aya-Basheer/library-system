<?php
namespace LibrarySystem\Traits;

trait IdGeneratorTrait {
    public function generateId(): string {
        return uniqid("B");
    }
}
