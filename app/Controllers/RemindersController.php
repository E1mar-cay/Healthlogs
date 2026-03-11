<?php

class RemindersController extends Controller
{
    public function index(): void
    {
        $this->view('reminders');
    }
}
