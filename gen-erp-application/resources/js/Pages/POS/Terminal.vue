<template>
    <AppLayout>
        <div class="h-screen flex flex-col bg-gray-50 dark:bg-gray-900">
            <!-- Top Bar -->
            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-2">
                            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-xl font-bold text-gray-900 dark:text-white">POS Terminal</h1>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ activeSession?.branch?.name || 'No Active Session' }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-3">
                        <button
                            v-if="activeSession"
                            @click="showSessionInfo = true"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                        >
                            Session Info
                        </button>
                        <button
                            v-if="!activeSession"
                            @click="showSessionModal = true"
                            class="px-6 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-lg"
                        >
                            Open Session
                        </button>
                        <button
                            v-else
                            @click="confirmCloseSession"
                            class="px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors"
                        >
                            Close Session
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div v-if="!activeSession" class="flex-1 flex items-center justify-center p-6">
                <div class="text-center max-w-md">
                    <div class="w-24 h-24 bg-yellow-100 dark:bg-yellow-900/20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-12 h-12 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">No Active Session</h2>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">Please open a POS session to start processing sales and transactions</p>
                    <button
                        @click="showSessionModal = true"
                        class="px-8 py-3 text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors font-semibold shadow-lg"
                    >
                        Open New Session
                    </button>
                </div>
            </div>

            <div v-else class="flex-1 flex overflow-hidden">
                <!-- Left: Menu/Products -->
                <div class="flex-1 flex flex-col overflow-hidden">
                    <!-- Category Tabs -->
                    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-3">
                        <div class="flex items-center space-x-2 overflow-x-auto">
                            <button
                                v-for="category in categories"
                                :key="category.id"
                                @click="selectedCategory = category.id"
                                :class="[
                                    'px-6 py-2 rounded-lg font-medium text-sm whitespace-nowrap transition-colors',
                                    selectedCategory === category.id
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                                ]"
                            >
                                {{ category.name }}
                            </button>
                        </div>
                    </div>

                    <!-- Search Bar -->
                    <div class="bg-white dark:bg-gray-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search menu items..."
                                class="w-full pl-10 pr-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:text-white"
                            />
                        </div>
                    </div>

                    <!-- Products Grid -->
                    <div class="flex-1 overflow-y-auto bg-gray-50 dark:bg-gray-900 p-6">
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <button
                                v-for="product in filteredProducts"
                                :key="product.id"
                                @click="addToCart(product)"
                                class="bg-white dark:bg-gray-800 rounded-xl p-4 hover:shadow-lg transition-all duration-200 border-2 border-transparent hover:border-blue-500 group"
                            >
                                <div class="aspect-square bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-gray-700 dark:to-gray-600 rounded-lg mb-3 flex items-center justify-center overflow-hidden">
                                    <img v-if="product.image" :src="product.image" :alt="product.name" class="w-full h-full object-cover" />
                                    <svg v-else class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <h3 class="font-semibold text-gray-900 dark:text-white text-sm mb-1 line-clamp-2">{{ product.name }}</h3>
                                <p class="text-blue-600 dark:text-blue-400 font-bold text-lg">{{ formatCurrency(product.unit_price) }}</p>
                                <div v-if="product.stock_quantity !== undefined" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Stock: {{ product.stock_quantity }}
                                </div>
                            </button>
                        </div>

                        <div v-if="filteredProducts.length === 0" class="flex flex-col items-center justify-center h-64">
                            <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400 text-lg">No products found</p>
                        </div>
                    </div>
                </div>

                <!-- Right: Order Cart -->
                <div class="w-96 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 flex flex-col">
                    <!-- Order Header -->
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-2">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Current Order</h2>
                            <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full text-sm font-semibold">
                                #{{ currentOrderNumber }}
                            </span>
                        </div>
                        <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ currentTime }}</span>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="flex-1 overflow-y-auto px-6 py-4">
                        <div v-if="cart.length === 0" class="flex flex-col items-center justify-center h-full text-center">
                            <svg class="w-20 h-20 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400 font-medium">Cart is empty</p>
                            <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Add items to start an order</p>
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="(item, index) in cart"
                                :key="index"
                                class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            >
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-900 dark:text-white text-sm">{{ item.name }}</h4>
                                        <p class="text-blue-600 dark:text-blue-400 font-medium text-sm">{{ formatCurrency(item.unit_price) }}</p>
                                    </div>
                                    <button
                                        @click="removeFromCart(index)"
                                        class="text-red-500 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300 p-1"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>

                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2 bg-white dark:bg-gray-800 rounded-lg p-1">
                                        <button
                                            @click="decrementQuantity(index)"
                                            class="w-8 h-8 flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                            </svg>
                                        </button>
                                        <span class="w-10 text-center font-bold text-gray-900 dark:text-white">{{ item.quantity }}</span>
                                        <button
                                            @click="incrementQuantity(index)"
                                            class="w-8 h-8 flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                    </div>
                                    <span class="font-bold text-gray-900 dark:text-white">{{ formatCurrency(item.unit_price * item.quantity) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary & Payment -->
                    <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4 space-y-4">
                        <!-- Totals -->
                        <div class="space-y-2">
                            <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                <span>Subtotal</span>
                                <span class="font-medium">{{ formatCurrency(subtotal) }}</span>
                            </div>
                            <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                <span>Tax ({{ taxRate }}%)</span>
                                <span class="font-medium">{{ formatCurrency(taxAmount) }}</span>
                            </div>
                            <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                <span>Discount</span>
                                <span class="font-medium text-green-600 dark:text-green-400">-{{ formatCurrency(discountAmount) }}</span>
                            </div>
                            <div class="h-px bg-gray-200 dark:bg-gray-700"></div>
                            <div class="flex justify-between text-xl font-bold text-gray-900 dark:text-white">
                                <span>Total</span>
                                <span>{{ formatCurrency(total) }}</span>
                            </div>
                        </div>

                        <!-- Customer Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Customer (Optional)</label>
                            <select
                                v-model="selectedCustomer"
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:text-white"
                            >
                                <option value="">Walk-in Customer</option>
                                <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                    {{ customer.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Payment Method -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Payment Method</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    v-for="method in paymentMethods"
                                    :key="method.id"
                                    @click="selectedPaymentMethod = method.id"
                                    :class="[
                                        'px-4 py-3 rounded-lg font-medium text-sm transition-all',
                                        selectedPaymentMethod === method.id
                                            ? 'bg-blue-600 text-white shadow-lg'
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                                    ]"
                                >
                                    {{ method.name }}
                                </button>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="space-y-2">
                            <button
                                @click="processPayment"
                                :disabled="cart.length === 0 || !selectedPaymentMethod || processing"
                                :class="[
                                    'w-full px-6 py-4 rounded-lg font-bold text-lg transition-all shadow-lg',
                                    cart.length === 0 || !selectedPaymentMethod || processing
                                        ? 'bg-gray-300 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                                        : 'bg-blue-600 text-white hover:bg-blue-700 active:scale-95'
                                ]"
                            >
                                <span v-if="processing" class="flex items-center justify-center">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Processing...
                                </span>
                                <span v-else>Complete Payment</span>
                            </button>

                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    @click="clearCart"
                                    class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 font-medium transition-colors"
                                >
                                    Clear
                                </button>
                                <button
                                    @click="holdOrder"
                                    class="px-4 py-2.5 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-lg hover:bg-yellow-200 dark:hover:bg-yellow-900/50 font-medium transition-colors"
                                >
                                    Hold
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Open Session Modal -->
        <Transition name="modal">
            <div v-if="showSessionModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full transform transition-all">
                    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Open POS Session</h2>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">Start a new session to begin processing sales</p>
                    </div>
                    
                    <div class="px-6 py-5 space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Branch</label>
                            <select
                                v-model="sessionForm.branch_id"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:text-white"
                            >
                                <option value="">Select Branch</option>
                                <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                                    {{ branch.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Opening Cash Amount</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">৳</span>
                                <input
                                    v-model="sessionForm.opening_cash"
                                    type="number"
                                    step="0.01"
                                    placeholder="0.00"
                                    class="w-full pl-10 pr-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:text-white"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Notes (Optional)</label>
                            <textarea
                                v-model="sessionForm.notes"
                                rows="3"
                                placeholder="Add any notes about this session..."
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:text-white resize-none"
                            ></textarea>
                        </div>
                    </div>

                    <div class="px-6 py-5 bg-gray-50 dark:bg-gray-700/50 rounded-b-2xl flex space-x-3">
                        <button
                            @click="showSessionModal = false"
                            class="flex-1 px-4 py-3 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 font-medium transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            @click="openSession"
                            :disabled="!sessionForm.branch_id || openingSession"
                            class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed font-semibold transition-colors shadow-lg"
                        >
                            {{ openingSession ? 'Opening...' : 'Open Session' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Session Info Modal -->
        <Transition name="modal">
            <div v-if="showSessionInfo && activeSession" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full">
                    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Session Information</h2>
                    </div>
                    
                    <div class="px-6 py-5 space-y-4">
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-medium text-blue-900 dark:text-blue-300">Status</span>
                                <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full text-xs font-semibold">Active</span>
                            </div>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-blue-700 dark:text-blue-400">Branch:</span>
                                    <span class="font-semibold text-blue-900 dark:text-blue-200">{{ activeSession.branch?.name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-blue-700 dark:text-blue-400">Opened:</span>
                                    <span class="font-semibold text-blue-900 dark:text-blue-200">{{ formatDateTime(activeSession.opened_at) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-blue-700 dark:text-blue-400">Opened By:</span>
                                    <span class="font-semibold text-blue-900 dark:text-blue-200">{{ activeSession.opened_by?.name }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Opening Cash</p>
                                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatCurrency(activeSession.opening_cash) }}</p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Current Cash</p>
                                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatCurrency(activeSession.opening_cash + totalSalesToday) }}</p>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">Today's Sales</p>
                            <div class="flex items-baseline space-x-2">
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ salesCountToday }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">transactions</p>
                            </div>
                            <p class="text-lg font-semibold text-blue-600 dark:text-blue-400 mt-1">{{ formatCurrency(totalSalesToday) }}</p>
                        </div>
                    </div>

                    <div class="px-6 py-5 bg-gray-50 dark:bg-gray-700/50 rounded-b-2xl">
                        <button
                            @click="showSessionInfo = false"
                            class="w-full px-4 py-3 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 font-medium transition-colors"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Payment Success Modal -->
        <Transition name="modal">
            <div v-if="showSuccessModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full">
                    <div class="px-6 py-8 text-center">
                        <div class="w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Payment Successful!</h2>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">Transaction completed successfully</p>
                        
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 mb-6">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Amount</div>
                            <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(lastSaleAmount) }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mt-2">Order #{{ lastSaleNumber }}</div>
                        </div>

                        <div class="space-y-2">
                            <button
                                @click="printReceipt"
                                class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold transition-colors shadow-lg"
                            >
                                Print Receipt
                            </button>
                            <button
                                @click="closeSuccessModal"
                                class="w-full px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 font-medium transition-colors"
                            >
                                New Order
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatCurrency, formatDate } from '@/utils/formatters';
import axios from 'axios';

// State
const activeSession = ref(null);
const cart = ref([]);
const products = ref([]);
const branches = ref([]);
const customers = ref([]);
const paymentMethods = ref([]);
const categories = ref([
    { id: 'all', name: 'All Items' },
    { id: 'main', name: 'Main Course' },
    { id: 'appetizer', name: 'Appetizer' },
    { id: 'soups', name: 'Soups' },
    { id: 'salad', name: 'Salad' },
    { id: 'drink', name: 'Drinks' },
]);

const searchQuery = ref('');
const selectedCategory = ref('all');
const selectedCustomer = ref('');
const selectedPaymentMethod = ref('');
const processing = ref(false);
const openingSession = ref(false);
const showSessionModal = ref(false);
const showSessionInfo = ref(false);
const showSuccessModal = ref(false);

const currentTime = ref('');
const currentOrderNumber = ref('');
const lastSaleAmount = ref(0);
const lastSaleNumber = ref('');
const salesCountToday = ref(0);
const totalSalesToday = ref(0);

const taxRate = ref(0);
const discountAmount = ref(0);

const sessionForm = ref({
    branch_id: '',
    opening_cash: 0,
    notes: '',
});

// Computed
const filteredProducts = computed(() => {
    let filtered = products.value;
    
    if (selectedCategory.value !== 'all') {
        filtered = filtered.filter(p => p.category === selectedCategory.value);
    }
    
    if (searchQuery.value) {
        filtered = filtered.filter(p => 
            p.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (p.sku && p.sku.toLowerCase().includes(searchQuery.value.toLowerCase()))
        );
    }
    
    return filtered;
});

const subtotal = computed(() => {
    return cart.value.reduce((sum, item) => sum + (item.unit_price * item.quantity), 0);
});

const taxAmount = computed(() => {
    return Math.round(subtotal.value * (taxRate.value / 100));
});

const total = computed(() => {
    return subtotal.value + taxAmount.value - discountAmount.value;
});

// Methods
const formatDateTime = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const updateTime = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
};

const generateOrderNumber = () => {
    const now = new Date();
    const timestamp = now.getTime().toString().slice(-6);
    currentOrderNumber.value = `ORD-${timestamp}`;
};

const addToCart = (product) => {
    const existingItem = cart.value.find(item => item.id === product.id);
    if (existingItem) {
        existingItem.quantity++;
    } else {
        cart.value.push({
            ...product,
            quantity: 1
        });
    }
};

const incrementQuantity = (index) => {
    cart.value[index].quantity++;
};

const decrementQuantity = (index) => {
    if (cart.value[index].quantity > 1) {
        cart.value[index].quantity--;
    } else {
        removeFromCart(index);
    }
};

const removeFromCart = (index) => {
    cart.value.splice(index, 1);
};

const clearCart = () => {
    if (cart.value.length > 0) {
        if (confirm('Are you sure you want to clear the cart?')) {
            cart.value = [];
            selectedCustomer.value = '';
            selectedPaymentMethod.value = '';
            generateOrderNumber();
        }
    }
};

const holdOrder = () => {
    if (cart.value.length > 0) {
        // Save to local storage or backend
        const heldOrders = JSON.parse(localStorage.getItem('heldOrders') || '[]');
        heldOrders.push({
            id: Date.now(),
            orderNumber: currentOrderNumber.value,
            items: [...cart.value],
            customer: selectedCustomer.value,
            timestamp: new Date().toISOString()
        });
        localStorage.setItem('heldOrders', JSON.stringify(heldOrders));
        
        clearCart();
        alert('Order held successfully');
    }
};

const processPayment = async () => {
    if (cart.value.length === 0 || !selectedPaymentMethod.value) {
        return;
    }

    processing.value = true;

    try {
        const response = await axios.post('/api/v1/pos/sales', {
            session_id: activeSession.value.id,
            customer_id: selectedCustomer.value || null,
            payment_method_id: selectedPaymentMethod.value,
            amount_tendered: total.value,
            items: cart.value.map(item => ({
                product_id: item.id,
                description: item.name,
                quantity: item.quantity,
                unit_price: item.unit_price,
                discount_amount: 0,
                tax_amount: 0
            }))
        });

        if (response.data.success) {
            lastSaleAmount.value = total.value;
            lastSaleNumber.value = response.data.data.sale_number;
            salesCountToday.value++;
            totalSalesToday.value += total.value;
            
            showSuccessModal.value = true;
            cart.value = [];
            selectedCustomer.value = '';
            selectedPaymentMethod.value = '';
            generateOrderNumber();
        }
    } catch (error) {
        console.error('Payment error:', error);
        alert(error.response?.data?.message || 'Payment failed. Please try again.');
    } finally {
        processing.value = false;
    }
};

const openSession = async () => {
    if (!sessionForm.value.branch_id) {
        alert('Please select a branch');
        return;
    }

    openingSession.value = true;

    try {
        const response = await axios.post('/api/v1/pos/sessions', {
            branch_id: sessionForm.value.branch_id,
            opening_cash: Math.round(sessionForm.value.opening_cash * 100), // Convert to paisa
            notes: sessionForm.value.notes
        });

        if (response.data.success) {
            activeSession.value = response.data.data;
            showSessionModal.value = false;
            sessionForm.value = {
                branch_id: '',
                opening_cash: 0,
                notes: ''
            };
            generateOrderNumber();
        }
    } catch (error) {
        console.error('Session open error:', error);
        alert(error.response?.data?.message || 'Failed to open session. Please try again.');
    } finally {
        openingSession.value = false;
    }
};

const confirmCloseSession = () => {
    if (cart.value.length > 0) {
        alert('Please complete or clear the current order before closing the session.');
        return;
    }

    if (confirm('Are you sure you want to close this session? This action cannot be undone.')) {
        closeSession();
    }
};

const closeSession = async () => {
    try {
        const closingCash = prompt('Enter closing cash amount:');
        if (closingCash === null) return;

        const response = await axios.post(`/api/v1/pos/sessions/${activeSession.value.id}/close`, {
            closing_cash: Math.round(parseFloat(closingCash) * 100),
            notes: ''
        });

        if (response.data.success) {
            activeSession.value = null;
            cart.value = [];
            selectedCustomer.value = '';
            selectedPaymentMethod.value = '';
            salesCountToday.value = 0;
            totalSalesToday.value = 0;
        }
    } catch (error) {
        console.error('Session close error:', error);
        alert(error.response?.data?.message || 'Failed to close session. Please try again.');
    }
};

const printReceipt = () => {
    // Generate receipt HTML
    const receiptWindow = window.open('', '_blank', 'width=300,height=600');
    const receiptHTML = `
        <!DOCTYPE html>
        <` + `html>
        <` + `head>
            <title>Receipt - ${lastSaleNumber.value}</title>
            <` + `style>
                body {
                    font-family: 'Courier New', monospace;
                    width: 280px;
                    margin: 0 auto;
                    padding: 10px;
                    font-size: 12px;
                }
                .header {
                    text-align: center;
                    border-bottom: 2px dashed #000;
                    padding-bottom: 10px;
                    margin-bottom: 10px;
                }
                .company-name {
                    font-size: 18px;
                    font-weight: bold;
                    margin-bottom: 5px;
                }
                .info-line {
                    margin: 3px 0;
                }
                .items {
                    border-bottom: 2px dashed #000;
                    padding-bottom: 10px;
                    margin-bottom: 10px;
                }
                .item {
                    display: flex;
                    justify-between;
                    margin: 5px 0;
                }
                .totals {
                    border-bottom: 2px dashed #000;
                    padding-bottom: 10px;
                    margin-bottom: 10px;
                }
                .total-line {
                    display: flex;
                    justify-between;
                    margin: 5px 0;
                }
                .total-line.grand {
                    font-size: 16px;
                    font-weight: bold;
                    margin-top: 10px;
                }
                .footer {
                    text-align: center;
                    margin-top: 10px;
                }
                @media print {
                    body { width: 280px; }
                }
            <` + `/style>
        <` + `/head>
        <` + `body>
            <` + `div class="header">
                <` + `div class="company-name">${activeSession.value?.branch?.name || 'POS Terminal'}<` + `/div>
                <` + `div class="info-line">Receipt #: ${lastSaleNumber.value}<` + `/div>
                <` + `div class="info-line">Date: ${new Date().toLocaleString()}<` + `/div>
                <` + `div class="info-line">Cashier: ${activeSession.value?.opened_by?.name || 'Staff'}<` + `/div>
            <` + `/div>
            
            <` + `div class="items">
                ${cart.value.map(item => `
                    <` + `div class="item">
                        <` + `span>${item.name} x${item.quantity}<` + `/span>
                        <` + `span>${formatCurrency(item.unit_price * item.quantity)}<` + `/span>
                    <` + `/div>
                `).join('')}
            <` + `/div>
            
            <` + `div class="totals">
                <` + `div class="total-line">
                    <` + `span>Subtotal:<` + `/span>
                    <` + `span>${formatCurrency(lastSaleAmount.value)}<` + `/span>
                <` + `/div>
                <` + `div class="total-line grand">
                    <` + `span>TOTAL:<` + `/span>
                    <` + `span>${formatCurrency(lastSaleAmount.value)}<` + `/span>
                <` + `/div>
            <` + `/div>
            
            <` + `div class="footer">
                <` + `p>Thank you for your purchase!<` + `/p>
                <` + `p>Please come again<` + `/p>
            <` + `/div>
            
            <` + `script>
                window.onload = function() {
                    window.print();
                    setTimeout(function() { window.close(); }, 100);
                };
            <` + `/script>
        <` + `/body>
        <` + `/html>
    `;
    
    receiptWindow.document.write(receiptHTML);
    receiptWindow.document.close();
};

const closeSuccessModal = () => {
    showSuccessModal.value = false;
    generateOrderNumber();
};

const loadActiveSession = async () => {
    try {
        const response = await axios.get('/api/v1/pos/sessions/active');
        if (response.data.success && response.data.data.length > 0) {
            activeSession.value = response.data.data[0];
            generateOrderNumber();
        }
    } catch (error) {
        console.error('Failed to load active session:', error);
    }
};

const loadProducts = async () => {
    try {
        // Mock data for now - replace with actual API call
        products.value = [
            { id: 1, name: 'Caramel Java Ice', unit_price: 4500, category: 'drink', stock_quantity: 50 },
            { id: 2, name: 'Chicken Burger', unit_price: 8500, category: 'main', stock_quantity: 30 },
            { id: 3, name: 'Caesar Salad', unit_price: 6500, category: 'salad', stock_quantity: 25 },
            { id: 4, name: 'French Fries', unit_price: 3500, category: 'appetizer', stock_quantity: 40 },
            { id: 5, name: 'Tomato Soup', unit_price: 4500, category: 'soups', stock_quantity: 20 },
            { id: 6, name: 'Grilled Chicken', unit_price: 12500, category: 'main', stock_quantity: 15 },
        ];
    } catch (error) {
        console.error('Failed to load products:', error);
    }
};

const loadBranches = async () => {
    try {
        // Mock data - replace with actual API call
        branches.value = [
            { id: 1, name: 'Main Branch' },
            { id: 2, name: 'Downtown Branch' },
        ];
    } catch (error) {
        console.error('Failed to load branches:', error);
    }
};

const loadPaymentMethods = async () => {
    try {
        // Mock data - replace with actual API call
        paymentMethods.value = [
            { id: 1, name: 'Cash' },
            { id: 2, name: 'Card' },
            { id: 3, name: 'bKash' },
            { id: 4, name: 'Nagad' },
        ];
    } catch (error) {
        console.error('Failed to load payment methods:', error);
    }
};

const loadCustomers = async () => {
    try {
        // Mock data - replace with actual API call
        customers.value = [];
    } catch (error) {
        console.error('Failed to load customers:', error);
    }
};

let timeInterval;

onMounted(() => {
    updateTime();
    timeInterval = setInterval(updateTime, 1000);
    generateOrderNumber();
    
    loadActiveSession();
    loadProducts();
    loadBranches();
    loadPaymentMethods();
    loadCustomers();
});

onUnmounted(() => {
    if (timeInterval) {
        clearInterval(timeInterval);
    }
});
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-active .bg-white,
.modal-leave-active .bg-white {
    transition: transform 0.3s ease;
}

.modal-enter-from .bg-white,
.modal-leave-to .bg-white {
    transform: scale(0.9);
}
</style>
