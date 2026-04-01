<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(Request $request): View
    {
        return view('shop', [
            'products' => $this->products(),
            'cartCount' => $this->cartCount($request),
        ]);
    }

    public function show(Request $request, string $productId): View
    {
        $product = $this->products()->firstWhere('id', $productId);
        abort_unless($product, 404);

        return view('shop-product', [
            'product' => $product,
            'cartCount' => $this->cartCount($request),
        ]);
    }

    public function cart(Request $request): View
    {
        $items = $this->cartItems($request);

        return view('cart', [
            'items' => $items,
            'subtotal' => collect($items)->sum(fn (array $item) => $item['line_total']),
            'cartCount' => $this->cartCount($request),
        ]);
    }

    public function addToCart(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'string'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $products = $this->products()->keyBy('id');
        $product = $products->get($validated['product_id']);
        abort_unless($product, 404);

        $quantity = (int) ($validated['quantity'] ?? 1);
        $cart = $request->session()->get('shop_cart', []);
        $existingQty = (int) ($cart[$product['id']]['quantity'] ?? 0);
        $newQty = min($existingQty + $quantity, 100);

        $cart[$product['id']] = [
            'product_id' => $product['id'],
            'name' => $product['name'],
            'category' => $product['category'],
            'price' => (int) $product['price'],
            'image' => $product['image'],
            'quantity' => $newQty,
        ];

        $request->session()->put('shop_cart', $cart);

        return back()->with('shop_notice', $product['name'].' added to cart.');
    }

    public function updateCart(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $cart = $request->session()->get('shop_cart', []);
        if (! isset($cart[$validated['product_id']])) {
            return back();
        }

        $cart[$validated['product_id']]['quantity'] = (int) $validated['quantity'];
        $request->session()->put('shop_cart', $cart);

        return back()->with('shop_notice', 'Cart updated.');
    }

    public function removeFromCart(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'string'],
        ]);

        $cart = $request->session()->get('shop_cart', []);
        unset($cart[$validated['product_id']]);
        $request->session()->put('shop_cart', $cart);

        return back()->with('shop_notice', 'Item removed.');
    }

    public function checkout(Request $request): View|RedirectResponse
    {
        $items = $this->cartItems($request);
        if ($items === []) {
            return redirect()->route('shop.cart')->with('shop_notice', 'Your cart is empty.');
        }

        $subtotal = collect($items)->sum(fn (array $item) => $item['line_total']);
        $shipping = 2500;

        return view('checkout', [
            'items' => $items,
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total' => $subtotal + $shipping,
            'cartCount' => $this->cartCount($request),
        ]);
    }

    public function placeOrder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:500'],
            'payment_method' => ['required', 'in:mobile_money,card,cash_on_delivery'],
        ]);

        $items = $this->cartItems($request);
        if ($items === []) {
            return redirect()->route('shop.cart')->with('shop_notice', 'Your cart is empty.');
        }

        $subtotal = collect($items)->sum(fn (array $item) => $item['line_total']);
        $shipping = 2500;
        $total = $subtotal + $shipping;

        $order = [
            'order_number' => 'BP-'.strtoupper(substr(md5((string) now()->timestamp), 0, 8)),
            'customer' => [
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
            ],
            'payment_method' => $validated['payment_method'],
            'items' => $items,
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total' => $total,
        ];

        $request->session()->put('last_order', $order);
        $request->session()->forget('shop_cart');

        return redirect()->route('shop.success');
    }

    public function success(Request $request): View|RedirectResponse
    {
        $order = $request->session()->get('last_order');
        if (! $order) {
            return redirect()->route('shop.index');
        }

        return view('shop-success', [
            'order' => $order,
            'cartCount' => $this->cartCount($request),
        ]);
    }

    private function cartItems(Request $request): array
    {
        $cart = $request->session()->get('shop_cart', []);

        return collect($cart)->map(function (array $item) {
            $item['line_total'] = $item['price'] * $item['quantity'];

            return $item;
        })->values()->all();
    }

    private function cartCount(Request $request): int
    {
        $cart = $request->session()->get('shop_cart', []);

        return (int) collect($cart)->sum('quantity');
    }

    private function products()
    {
        return collect([
            ['id' => 'beef-prime-cuts', 'name' => 'Prime Beef Cuts', 'category' => 'Beef', 'price' => 18500, 'unit' => 'kg', 'badge' => 'Best Seller', 'image' => 'https://images.unsplash.com/photo-1603048297172-c92544798d5a?auto=format&fit=crop&w=1200&q=80'],
            ['id' => 'goat-fresh', 'name' => 'Fresh Goat Meat', 'category' => 'Goat', 'price' => 13000, 'unit' => 'kg', 'badge' => 'Popular', 'image' => 'https://images.unsplash.com/photo-1559561853-08451507cbe7?auto=format&fit=crop&w=1200&q=80'],
            ['id' => 'whole-chicken', 'name' => 'Whole Chicken', 'category' => 'Poultry', 'price' => 9500, 'unit' => 'kg', 'badge' => 'Farm Fresh', 'image' => 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?auto=format&fit=crop&w=1200&q=80'],
            ['id' => 'tilapia-fillet', 'name' => 'Tilapia Fillet', 'category' => 'Fish', 'price' => 11000, 'unit' => 'kg', 'badge' => 'New', 'image' => 'https://images.unsplash.com/photo-1510130387422-82bed34b37e9?auto=format&fit=crop&w=1200&q=80'],
        ]);
    }
}
