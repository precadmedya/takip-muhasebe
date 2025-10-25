<?php
class Logger {
    protected string $file;
    public function __construct(string $file) {
        $this->file = $file;
    }
    public function info(string $message): void {
        $date = date('Y-m-d H:i:s');
        file_put_contents($this->file, "[$date] $message\n", FILE_APPEND);
    }
}
