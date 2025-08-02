<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ProductService
{
    private $jsonFile = 'products.json';

    public function getAllProducts()
    {
        return $this->loadProductsFromJson();
    }

    public function createProduct($data)
    {
        $totalValue = (int) $data['quantity_in_stock'] * (float)$data['price_per_item'];

        $product = [
            'id' => uniqid(),
            'name' => $data['name'],
            'quantity_in_stock' => (int) $data['quantity_in_stock'],
            'price_per_item' => (float) $data['price_per_item'],
            'datetime_submitted' => Carbon::now()->toISOString(),
            'total_value' => $totalValue,
        ];

        $products = $this->loadProductsFromJson();

        $products[] = $product;

        $this->saveProductsToJson($products);

        return $product;
    }

    public function updateProduct($id, $data)
    {
        $products = $this->loadProductsFromJson();
        
        foreach ($products as &$product) {
            if ($product['id'] === $id) {
                $product['name'] = $data['name'];
                $product['quantity_in_stock'] = (int)$data['quantity_in_stock'];
                $product['price_per_item'] = (float)$data['price_per_item'];
                $product['total_value'] = (int)$data['quantity_in_stock'] * (float)$data['price_per_item'];
                break;
            }
        }

        $this->saveProductsToJson($products);

        return $product;
    }

    public function deleteProduct($id)
    {
        $products = $this->loadProductsFromJson();
        $products = array_filter($products, function($product) use ($id) {
            return $product['id'] !== $id;
        });

        $this->saveProductsToJson(array_values($products));

        return true;
    }

    private function loadProductsFromJson()
    {
        if (!Storage::exists($this->jsonFile)) {
            return [];
        }

        $content = Storage::get($this->jsonFile);

        $products = json_decode($content, true) ?: [];

        foreach($products as $key => $product) {
            $products[$key]['quantity_in_stock_formatted'] = number_format($product['quantity_in_stock'], 0);
            $products[$key]['price_per_item_formatted'] = number_format($product['price_per_item'], 2);
            $products[$key]['total_value_formatted'] = number_format($product['total_value'], 2);
        }

        return $products;
    }

    private function saveProductsToJson($products)
    {
        // Sort by datetime submitted (newest first)
        usort($products, function($a, $b) {
            return strtotime($b['datetime_submitted']) - strtotime($a['datetime_submitted']);
        });

        Storage::put($this->jsonFile, json_encode($products, JSON_PRETTY_PRINT));
    }
} 