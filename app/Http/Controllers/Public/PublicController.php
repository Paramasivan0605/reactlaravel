<?php

namespace App\Http\Controllers\Public;

use App\Enums\OrderStatusEnum;
use App\Enums\ReservationStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderDetail;
use App\Models\DiningTable;
use App\Models\FoodCategory;
use App\Models\FoodMenu;
use App\Models\FoodLocation;
use App\Models\Location;
use App\Models\PromotionDiscount;
use App\Models\PromotionEvent;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class PublicController extends Controller
{
    public function login()
    {
        $locations = Location::all();
        return view('public.login', compact('locations'));
    }

    // Customer Login / Submit Function

    public function submit(Request $request): RedirectResponse
    {
        // ✅ Validate incoming request
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'mobile' => 'required|numeric',
            'location' => 'required|exists:location,location_id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        // ✅ Find or create customer
        $customer = Customer::where('mobile', $validated['mobile'])->first();

        if (!$customer) {
            $customer = Customer::create([
                'name' => $validated['name'],
                'mobile' => $validated['mobile'],
            ]);
            Log::info('New customer created: ' . $customer->name . ' (ID: ' . $customer->id . ')');
        } else {
            Log::info('Customer logged in: ' . $customer->name . ' (ID: ' . $customer->id . ')');
        }

        // ✅ Handle cart restoration & pending order for EXISTING customers
        if ($customer->wasRecentlyCreated === false) {

            // --- Check for existing cart at this location ---
            $cartCount = Cart::where('user_id', $customer->id)
                ->where('location_id', $validated['location'])
                ->count();

            if ($cartCount > 0) {
                session(['restore_cart' => true]);
            } else {
                session()->forget('restore_cart'); // clear stale session
            }

            // --- Check for pending orders ---
            $lastOrder = CustomerOrder::where('customer_id', $customer->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($lastOrder && !in_array($lastOrder->order_status, ['completed', 'cancelled'])) {
                session(['pending_order' => $lastOrder->id]);
            } else {
                session()->forget('pending_order'); // clear stale session
            }

        } else {
            // New customer — no restore or pending order
            session()->forget(['restore_cart', 'pending_order']);
        }

        // ✅ Store login info in session
        session([
            'customer_id'     => $customer->id,
            'customer_name'   => $customer->name,
            'customer_mobile' => $customer->mobile,
            'location_id'     => $validated['location'],
        ]);

        Log::info('Login submitted for customer: ' . $customer->name . ' (ID: ' . $customer->id . ') at location: ' . $validated['location']);

        // ✅ Redirect to menu page
        return redirect()
            ->route('location.menu', ['id' => $validated['location']])
            ->with('success-message', 'Welcome, ' . $customer->name . '!');
    }

    public function home(): View
    {
        $menu = FoodMenu::all();
        return view('public.home', compact('menu'));
    }

    public function menu(): View
    {
        $menu = FoodMenu::all();
        $category = FoodCategory::all();
        $locations = Location::all();

        return view('public.menu', compact('menu', 'category', 'locations'));
    }

   // Location Menu Page Function
    public function locationMenuPage($locationId)
    {
        // ✅ Get location details
        $location = Location::findOrFail($locationId);

        // ✅ Get all categories that have food items in this location
        $categories = FoodCategory::select('food_categories.*')
            ->join('food_menus', 'food_menus.category_id', '=', 'food_categories.id')
            ->join('food_price', 'food_price.food_id', '=', 'food_menus.id')
            ->where('food_price.location_id', $locationId)
            ->distinct()
            ->get();

        // ✅ Get food menu with price for this location, grouped by category
        $foodMenu = FoodMenu::select('food_menus.*', 'food_price.price', 'food_categories.name as category')
            ->join('food_price', 'food_price.food_id', '=', 'food_menus.id')
            ->leftJoin('food_categories', 'food_categories.id', '=', 'food_menus.category_id')
            ->where('food_price.location_id', $locationId)
            ->orderBy('food_categories.name')
            ->get();

        // ✅ Initialize cart variables
        $cartItems = [];
        $cartTotal = 0;
        $cartCount = 0;
        $currency  = $location->currency;

        // ✅ Only load cart if customer logged in
        if (session('customer_id')) {
            $carts = Cart::where('user_id', session('customer_id'))
                ->where('location_id', $locationId)
                ->with(['food.locations', 'location'])
                ->get();

            foreach ($carts as $cart) {
                $price = $cart->getPrice();

                $cartItems[] = [
                    'id'            => $cart->food_id,
                    'name'          => $cart->food->name,
                    'image'         => $cart->food->image ? asset($cart->food->image) : asset('images/placeholder.jpg'),
                    'price'         => $price,
                    'quantity'      => $cart->quantity,
                    'delivery_type' => $cart->delivery_type,
                    'total'         => $cart->getTotalPrice(),
                ];

                $cartTotal += $cart->getTotalPrice();
                $cartCount += $cart->quantity;
            }
        }

        // ✅ Return view with categories
        return view('public.locationMenu', compact(
            'foodMenu',
            'location',
            'categories',
            'cartItems',
            'cartTotal',
            'cartCount',
            'currency'
        ))->with([
            'restore_cart'  => session('restore_cart', false),
            'pending_order' => session('pending_order', null),
        ]);
    }

    public function about(): View
    {
        return view('public.about');
    }

    public function promotion(): View
    {
        $currentMonth = Carbon::now();
        $monthBefore = Carbon::now()->subMonth();

        $promotion = PromotionEvent::where(function ($query) use ($currentMonth, $monthBefore) {
            $query->whereMonth('event_date', '=', $currentMonth)
                ->orWhereMonth('event_date', '=', $monthBefore);
        })->get();

        $coupon = [];
        foreach ($promotion as $event) {
            $eventCoupon = PromotionDiscount::where('event_id', $event->id)->get();
            $coupon[$event->id] = $eventCoupon;
        }

        $menu = FoodMenu::where('price', '>', 27.00)->get();

        return view('public.promotion', compact('promotion', 'coupon', 'menu'));
    }

    public function reservation(): View
    {
        return view('public.reservation');
    }

    // ==================== CART MANAGEMENT METHODS ====================
    
    public function addToCart(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'food_id' => 'required|exists:food_menus,id',
            'quantity' => 'required|integer|min:1',
            'delivery_type' => 'required|string',
            'location_id' => 'required|exists:location,location_id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $customerId = session('customer_id');
        if (!$customerId) {
            return response()->json([
                'success' => false,
                'message' => 'Please login first'
            ], 401);
        }

        // Check if cart has items with different delivery type or location
        $existingCart = Cart::where('user_id', $customerId)->first();
        
        if ($existingCart) {
            if ($existingCart->delivery_type !== $request->delivery_type) {
                return response()->json([
                    'success' => false,
                    'message' => "You already have items with delivery type '{$existingCart->delivery_type}'. Please clear your cart first."
                ], 422);
            }
            
            if ($existingCart->location_id != $request->location_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have items from a different location. Please clear your cart first.'
                ], 422);
            }
        }

        // Add or update cart item
        $cartItem = Cart::where('user_id', $customerId)
            ->where('food_id', $request->food_id)
            ->first();

        if ($cartItem) {
            $cartItem->quantity += $request->quantity;
            $cartItem->save();
        } else {
            Cart::create([
                'user_id' => $customerId,
                'food_id' => $request->food_id,
                'quantity' => $request->quantity,
                'delivery_type' => $request->delivery_type,
                'location_id' => $request->location_id
            ]);
        }

        $food = FoodMenu::find($request->food_id);

        return response()->json([
            'success' => true,
            'message' => "{$food->name} added to cart!",
            'cart_count' => Cart::where('user_id', $customerId)->sum('quantity')
        ]);
    }

    public function updateCartQuantity(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'food_id' => 'required|exists:food_menus,id',
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $customerId = session('customer_id');
        $cartItem = Cart::where('user_id', $customerId)
            ->where('food_id', $request->food_id)
            ->first();

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found in cart'
            ], 404);
        }

        $cartItem->quantity = $request->quantity;
        $cartItem->save();

        return response()->json([
            'success' => true,
            'message' => 'Cart updated successfully',
            'cart_count' => Cart::where('user_id', $customerId)->sum('quantity')
        ]);
    }
    public function updateDeliveryType(Request $request)
    {
        try {
            $customerId = session('customer_id');
            $deliveryType = $request->input('delivery_type');
            $locationId = $request->input('location_id');

            if (!$customerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not logged in'
                ]);
            }

            // Update delivery type for all cart items
            Cart::where('user_id', $customerId)
                ->where('location_id', $locationId)
                ->update(['delivery_type' => $deliveryType]);

            return response()->json([
                'success' => true,
                'message' => 'Delivery type updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating delivery type: ' . $e->getMessage()
            ]);
        }
    }
    public function removeFromCart(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'food_id' => 'required|exists:food_menus,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $customerId = session('customer_id');
        $cartItem = Cart::where('user_id', $customerId)
            ->where('food_id', $request->food_id)
            ->first();

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found in cart'
            ], 404);
        }

        $foodName = $cartItem->food->name;
        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => "{$foodName} removed from cart",
            'cart_count' => Cart::where('user_id', $customerId)->sum('quantity')
        ]);
    }

    public function clearCart(): JsonResponse
    {
        $customerId = session('customer_id');
        Cart::where('user_id', $customerId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared successfully'
        ]);
    }

    public function getCart(): JsonResponse
    {
        $customerId = session('customer_id');
        $locationId = session('location_id'); // Get current location from session
        
        // ✅ Filter cart by BOTH customer_id AND location_id
        $carts = Cart::where('user_id', $customerId)
            ->where('location_id', $locationId)
            ->with(['food', 'location'])
            ->get();

        $cartItems = [];
        $totalAmount = 0;
        $currency = '';

        foreach ($carts as $cart) {
            $food = $cart->food;
            $location = $cart->location;

            // Get price dynamically via pivot table method
            $price = $food->getPriceForLocation($cart->location_id);
            $itemTotal = $price * $cart->quantity;

            // Dynamic currency (fallback to 'RM' if missing)
            $currency = $location->currency ?? 'RM';

            // Use full stored image path
            $imageUrl = $food->image
                ? asset($food->image)
                : asset('images/no-image.jpg');

            $cartItems[] = [
                'id'            => $cart->food_id,
                'name'          => $food->name,
                'image'         => $imageUrl,
                'price'         => $price,
                'quantity'      => $cart->quantity,
                'delivery_type' => $cart->delivery_type,
                'location_id'   => $cart->location_id,
                'currency'      => $currency,
                'total'         => $itemTotal,
            ];
            
            $totalAmount += $itemTotal;
        }

        return response()->json([
            'success'      => true,
            'cart_items'   => $cartItems,
            'total_amount' => $totalAmount,
            'currency'     => $currency,
            'cart_count'   => $carts->sum('quantity'),
            'delivery_type'=> $carts->first()->delivery_type ?? null
        ]);
    }

    // ==================== ORDER MANAGEMENT ====================

    public function orderHistory(): View
    {
        $orders = [];
        if (session('customer_id')) {
            $orders = CustomerOrder::with(['customerOrderDetail.foodMenu.foodLocations', 'diningTable'])
                ->where('customer_id', session('customer_id'))
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('public.order-history', compact('orders'));
    }

    public function orderDetails($orderId): View
    {
        $order = CustomerOrder::with(['customerOrderDetail.foodMenu.foodLocations', 'diningTable'])
            ->where('customer_id', session('customer_id'))
            ->where('id', $orderId)
            ->firstOrFail();

        return view('public.order-details', compact('order'));
    }

    public static function getStatusClass($status)
    {
        if ($status instanceof \App\Enums\OrderStatusEnum) {
            $statusValue = $status->value;
        } else {
            $statusValue = $status;
        }

        $normalizedStatus = strtolower($statusValue);
        
        switch ($normalizedStatus) {
            case 'Ordered':
                return 'status-ordered';
            case 'preparing':
                return 'status-preparing';
            case 'ready_to_deliver':
                return 'status-ready';
            case 'delivery_on_the_way':
                return 'status-delivery';
            case 'delivered':
                return 'status-delivered';
            case 'completed':
                return 'status-completed';
            case 'cancelled':
                return 'status-cancelled';
            default:
                return 'status-ordered';
        }
    }

    public static function getStatusDisplay($status)
    {
        if ($status instanceof \App\Enums\OrderStatusEnum) {
            $statusValue = $status->value;
        } else {
            $statusValue = $status;
        }

        return ucwords(str_replace('_', ' ', $statusValue));
    }

    public static function getOrderTimeline($orderStatus)
    {
        if ($orderStatus instanceof \App\Enums\OrderStatusEnum) {
            $currentStatus = $orderStatus->value;
        } else {
            $currentStatus = $orderStatus;
        }
        
        $currentStatus = strtolower($currentStatus);
        
        $statuses = [
            'Ordered' => 'Order Placed',
            'preparing' => 'Preparing',
            'ready_to_deliver' => 'Ready',
            'delivery_on_the_way' => 'On the Way',
            'delivered' => 'Delivered',
            'completed' => 'Completed'
        ];
        
        return [
            'statuses' => $statuses,
            'currentStatus' => $currentStatus
        ];
    }

    public function getCustomerAddress($id): JsonResponse
    {
        try {
            $customer = Customer::find($id);
            
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'address' => ''
                ]);
            }

            return response()->json([
                'success' => true,
                'address' => $customer->address ?? '',
                'contact' => $customer->mobile ?? ''
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'address' => ''
            ]);
        }
    }

    public function search(Request $request): View
    {
        $keyword = $request->input('search');

        $search = FoodMenu::where(function ($query) use ($keyword) {
            $query->where('name', 'like', '%' . $keyword . '%')
                ->orWhere('price', 'like', '%' . $keyword . '%')
                ->orWhereHas('foodCategory', function ($query) use ($keyword) {
                    $query->where('name', 'like', '%' . $keyword . '%');
                });
        })->get();

        Log::info([$keyword, $search]);

        return view('public.menu', ['menu' => $search]);
    }

    public function createOrder(Request $request): JsonResponse
    {
        $tableNumber = $request->input('table_number');
        $contact = $request->input('customer_contact');
        $address = $request->input('customer_address');
        $paymentType = $request->input('payment_type');
        $additionalContact = $request->input('additional_contact');

        if (!in_array($paymentType, ['cash', 'card'])) {
            return response()->json([
                'validation-error-message' => 'Invalid payment method selected.',
            ], 422);
        }

        $customerId = session('customer_id');
        
        // Get cart items from database
        $cartItems = Cart::where('user_id', $customerId)
            ->with(['food', 'location'])
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'validation-error-message' => 'Your cart is empty.',
            ], 422);
        }

        $deliveryType = $cartItems->first()->delivery_type;
        $locationId = $cartItems->first()->location_id;
        $timezones = [
            1 => 'Asia/Bangkok',   // Phuket
            2 => 'Asia/Bangkok',   // Bangkok
            3 => 'Asia/Bangkok',   // Pattaya
            4 => 'Asia/Colombo',   // Colombo
        ];

        date_default_timezone_set($timezones[$locationId] ?? 'Asia/Bangkok');

        if ($deliveryType === 'Doorstep Delivery' && empty(trim($address))) {
            return response()->json([
                'validation-error-message' => 'Delivery address is required for doorstep delivery.',
            ], 422);
        }

        $tableId = null;
        if ($deliveryType === 'Restaurant Dine-in') {
            $table = DiningTable::where('table_name', $tableNumber)->first();

            if (!$table) {
                return response()->json([
                    'validation-error-message' => 'Table does not exist. Please enter a correct table number.',
                ], 422);
            }

            if ($table->isOccupied) {
                return response()->json([
                    'validation-error-message' => 'Table is taken. Please enter another table number.',
                ], 422);
            }

            $table->update(['isOccupied' => true]);
            $tableId = $table->id;
        }

        // Update customer address
        if ($customerId && $address) {
            $customer = Customer::find($customerId);
            if ($customer) {
                $customer->update(['address' => $address]);
                Log::info('Customer address updated for ID: ' . $customerId);
            }
        }

        // Calculate total from cart
        $totalPrice = 0;
        foreach ($cartItems as $cartItem) {
            $foodPrice = $cartItem->food->foodLocations
                ->where('id', $locationId)
                ->first();
            $price = $foodPrice ? $foodPrice->pivot->price : 0;
            $totalPrice += $price * $cartItem->quantity;
        }
        // Create order
        $order = CustomerOrder::create([
            'customer_id' => $customerId,
            'dining_table_id' => $tableId,
            'order_total_price' => $request->input('total_amount', $totalPrice),
            'delivery_type' => $deliveryType,
            'payment_type' => $paymentType,
            'isPaid' => false,
            'order_status' => OrderStatusEnum::Ordered,
            'customer_contact' => $additionalContact ? $additionalContact : $contact,
            'delivery_address' => $deliveryType === 'Doorstep Delivery' ? $address : null,
            'location_id' => $locationId,
        ]);

        // Create order details from cart
        foreach ($cartItems as $cartItem) {
            $foodPrice = $cartItem->food->foodLocations
                ->where('id', $locationId)
                ->first();
            $price = $foodPrice ? $foodPrice->pivot->price : 0;

            CustomerOrderDetail::create([
                'order_id' => $order->id,
                'food_id' => $cartItem->food_id,
                'quantity' => $cartItem->quantity,
                'total_price' => $price * $cartItem->quantity,
            ]);
        }

        // Clear cart after order is created
        Cart::where('user_id', $customerId)->delete();

        Log::info('Order created for customer ID: ' . $customerId . ', Order ID: ' . $order->id);

        return response()->json([
            'success-message' => 'Your order is being processed. Please wait 15–30 minutes.',
            'order_id' => $order->id
        ]);
    }

    public function makeReservation(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'book_name' => 'required|max:255',
            'book_email' => 'required|email',
            'book_phone' => 'required|numeric',
            'guest_number' => 'required|numeric|min:2',
            'book_date' => 'required|date|after:today',
            'book_time' => 'required|date_format:H:i',
            'book_message' => 'max:999999'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $reservation = Reservation::create([
            'customer_id' => session('customer_id'),
            'reservation_name' => $request->book_name,
            'reservation_email' => $request->book_email,
            'reservation_contact' => $request->book_phone,
            'reservation_attendees' => $request->guest_number,
            'reservation_date' => $request->book_date,
            'reservation_time' => $request->book_time,
            'reservation_message' => $request->book_message,
            'dining_table_id' => null,
            'reservation_status' => ReservationStatusEnum::Pending,
        ]);

        Log::info('Reservation created for customer ID: ' . session('customer_id') . ', Reservation ID: ' . $reservation->id);

        return back()->with('success-message', 'We have received your reservation. We will process immediately and we will contact you as soon as possible. Thank you.');
    }

    public function logout(): RedirectResponse
    {
        session()->forget(['customer_id', 'customer_name', 'customer_mobile', 'location_id']);
        return redirect()->route('welcome')->with('success', 'Logged out successfully.');
    }
    public function locationMenuApi($locationId)
{
    $location = Location::findOrFail($locationId);

    $categories = FoodCategory::select('food_categories.*')
        ->join('food_menus', 'food_menus.category_id', '=', 'food_categories.id')
        ->join('food_price', 'food_price.food_id', '=', 'food_menus.id')
        ->where('food_price.location_id', $locationId)
        ->distinct()
        ->get();

    $foodMenu = FoodMenu::select('food_menus.*', 'food_price.price', 'food_categories.name as category', 'food_categories.image as category_image')
        ->join('food_price', 'food_price.food_id', '=', 'food_menus.id')
        ->leftJoin('food_categories', 'food_categories.id', '=', 'food_menus.category_id')
        ->where('food_price.location_id', $locationId)
        ->orderBy('food_categories.name')
        ->get();

    // Convert images to full URLs for the client
    $categories = $categories->map(function($cat) {
        $cat->image = $cat->image ? asset(str_replace('\\','/',$cat->image)) : null;
        return $cat;
    });

    $foodMenu = $foodMenu->map(function($item) {
        // If category_image was stored as Windows path, normalize and turn to asset URL
        $item->category_image = $item->category_image ? asset(str_replace('\\','/',$item->category_image)) : null;
        return $item;
    });

    // cart stuff unchanged...
    $cartItems = [];
    $cartTotal = 0;
    $cartCount = 0;

    if (session('customer_id')) {
        $carts = Cart::where('user_id', session('customer_id'))
            ->where('location_id', $locationId)
            ->with('food')
            ->get();

        foreach ($carts as $cart) {
            $cartItems[] = [
                'id' => $cart->food_id,
                'name' => $cart->food->name,
                'price' => $cart->getPrice(),
                'quantity' => $cart->quantity,
                'total' => $cart->getTotalPrice()
            ];
            $cartTotal += $cart->getTotalPrice();
            $cartCount += $cart->quantity;
        }
    }

    return response()->json([
        'location'   => $location,
        'categories' => $categories,
        'foodMenu'   => $foodMenu,
        'cartItems'  => $cartItems,
        'cartTotal'  => $cartTotal,
        'cartCount'  => $cartCount,
        'currency'   => $location->currency
    ]);
}

}