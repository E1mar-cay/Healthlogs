<?php

class InventoryController extends Controller
{
    public function index(): void
    {
        $this->view('inventory');
    }
}
