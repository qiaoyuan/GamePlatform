<?php
declare (strict_types=1);

namespace util;

use app\common\model\OrderProductPassenger;
use app\common\model\Product;
use app\common\model\ProductPrice;

class Calc
{
    private Product $product;
    private string $date;
    private array $passengers;

    public function setProduct(Product $product): static
    {
        $this->product = $product;
        return $this;
    }

    public function setDate(string $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function setPassengers(array $passengers): static
    {
        $this->passengers = $passengers;
        return $this;
    }

    public function calc(): array
    {
        bcscale(2);
        $result = ['amount' => 0];
        $price = $this->product->getPrice($this->date);
        if ($price) {
            $result['price'] = $price->hidden(ProductPrice::$hiddenField)->toArray();
            foreach ($this->passengers as &$passenger) {
                $passenger['price'] = $price->price_other;
                $idCard = creditInfo($passenger['id_card'] ?? '');
                if ($idCard) {
                    $passenger['birth'] = $idCard['birth'];
                    $passenger['gender'] = $idCard['gender'] == 1 ? '男' : '女';
                }
                if (isset($passenger['is_disable']) && $passenger['is_disable'] && !empty($passenger['disable_id_card'])) {
                    $passenger['price'] = $price->price;
                }
                if (isset($passenger['birth'])) {
                    $age = OrderProductPassenger::calcAge($passenger['birth'], $this->date);
                    if ($age <= $price->kid_age && $price->price_kid > 0) {
                        $passenger['price'] = min($price->price_kid, $passenger['price']);
                    }
                    if ($age <= $price->minor_age && $price->minor_age > 0) {
                        $passenger['price'] = min($price->price_minor, $passenger['price']);
                    }
                }
                $result['amount'] = bcadd((string)$result['amount'], (string)$passenger['price']);
            }
            $result['passengers'] = $this->passengers;
        }
        return $result;
    }
}