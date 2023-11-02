<?php

namespace App\Http\Controllers\Sinkron;

use App\Http\Controllers\Controller;
use Revolution\Google\Sheets\Facades\Sheets;

class SinkronController extends Controller
{
    public function show()
    {
        $sheet_name = env('SHEET_NAME');
        $sheet_id = env('SPREDSHEET_ID');
        //        $values = Sheets::spreadsheet('YOUR_SPREADSHEET_ID')->sheet('Test')->get();
        $row = [7, 'ahmad7@gmail.com', 'ahmad7'];
        Sheets::spreadsheet('YOUR_SPREADSHEET_ID')->sheet('Test')->append([$row]);
        $values = Sheets::all();
        dd($values);
    }
}
