<?php

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->view('dashboard');
    }
}
