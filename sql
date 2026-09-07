DB::select("SELECT sql FROM sqlite_master WHERE type='table' AND name='employees'")[0]-;
