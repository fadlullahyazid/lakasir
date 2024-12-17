<?php

namespace App\Filament\Tenant\Pages\Traits;

use App\Models\Tenants\Product;
use App\Models\Tenants\Setting;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;

trait TableProduct
{
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // TODO: fix the query for product with this condition
                // * hide the prodcut when the type is product but that has a 0 stock
                // * show the product when the type is service but that has a 0 stock
                // * show the product when the type is procut but that has a 0 stock and then has a is_non_stock true
                Product::query()
                    ->where(function ($query) {
                        $query->where('type', 'product')
                            ->where(function ($query) {
                                $query->where('stock', '>', 0)
                                    ->orWhere('is_non_stock', true);
                            });
                    })
                    ->where('show', true)
                    // ->orWhere('type', 'service')
                    // ->limit(12)
                    ->orderBy('id', 'desc')
                    
            )
            ->paginated(false)
            ->columns([
                Stack::make([
                    ImageColumn::make('hero_image')
                        ->translateLabel()
                        ->alignCenter()
                        ->extraAttributes([
                            'class' => 'py-0',
                        ])
                        ->extraImgAttributes([
                            'class' => 'mb-4 object-cover w-full rounded-t-xl',
                        ])
                        ->height('100%'),
                    TextColumn::make('selling_price')
                        ->color('primary')
                        ->money(Setting::get('currency', 'IDR'))
                        ->columnStart(0)
                        ->extraAttributes([
                            'class' => 'font-bold mx-4',
                        ]),
                    TextColumn::make('name')
                        ->size('md')
                        ->searchable(['sku', 'name', 'barcode'])
                        ->extraAttributes([
                            'class' => 'font-bold mx-4',
                        ]),
                    TextColumn::make('stock')
                        ->hidden(function (Product $product) {
                            return $product->is_non_stock;
                        })
                        ->icon(function (Product $product) {
                            if ($product->is_non_stock) {
                                return '';
                            }

                            return $product->stock < Setting::get('minimum_stock_nofication', 10)
                                    ? 'heroicon-s-information-circle'
                                : '';
                        })
                        ->iconColor('danger')
                        ->extraAttributes([
                            'class' => 'font-bold',
                        ])
                        ->formatStateUsing(fn (Product $product) => __('Stock').': '.$product->stock),
                ])
                ->extraAttributes([
                    'class' => '-mx-4 -mt-4 h-full',
                ]),
            ])
            ->contentGrid([
                'md' => 4,
                'xl' => 5,
            ])
            ->headerActionsPosition(HeaderActionsPosition::Bottom)
            ->searchPlaceholder(__('Search (SKU, name, barcode)'))
            ->actions([
                Action::make('insert_amount')
                    ->translateLabel()
                    ->icon('heroicon-o-plus')
                    ->button()
                    ->form([
                        TextInput::make('amount')
                            ->translateLabel()
                            ->extraAttributes([
                                'focus',
                            ])
                            ->rules([
                                function (Product $product) {
                                    return function (string $attribute, $value, Closure $fail) use ($product) {
                                        if (! $this->validateStock($product, $value)) {
                                            $fail('Stock is out');
                                        }
                                    };
                                },
                            ])
                            ->default(1),
                    ])
                    ->extraAttributes([
                        'class' => 'mr-auto h-auto !absolute bottom-4',
                    ])
                    ->action(fn (Product $product, array $data) => $this->addCart($product, $data))
                    ->hiddenLabel(),

                Action::make('insert_amount')
                ->translateLabel()
                ->icon('heroicon-o-plus')
                ->button()
                ->form([
                    TextInput::make('amount')
                        ->translateLabel()
                        ->extraAttributes([
                            'focus',
                        ])
                        ->rules([
                            function (Product $product) {
                                return function (string $attribute, $value, Closure $fail) use ($product) {
                                    if (! $this->validateStock($product, $value)) {
                                        $fail('Stock is out');
                                    }
                                };
                            },
                        ])
                        ->default(1),
                ])
                ->extraAttributes([
                    'class' => 'invisible',
                ])
                ->action(fn (Product $product, array $data) => $this->addCart($product, $data))
                ->hiddenLabel(),
                Action::make('cart')
                    ->label(function (Product $product) {
                        return $product->CartItems()->first()?->qty ?? '';
                    })
                    ->color('white')
                    ->icon('heroicon-o-shopping-bag')
                    ->hidden(fn (Product $product) => ! $product->CartItems()->exists())
                    ->extraAttributes([
                        'class' => 'h-auto !absolute bottom-4 right-4',
                    ]),
            ]);
    }
}
