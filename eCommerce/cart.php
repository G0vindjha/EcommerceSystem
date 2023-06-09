<?php
//session starts
session_id("customerLoginSession");
session_start();
//Customer Login check
if (!isset($_SESSION['customer_id'])) {
    header("Location:index.php");
}
require_once './lib/Connection.php';
//Remove Product from cart by ajax call
if ($_POST['action'] == "deleteCartProduct") {
    $data = array(
        ":cart_id" => array(
            "value" => $_POST['value'],
            "type" => 'INT'
        ),
    );
    $conn = new Connection();
    $result = $conn->delete("Cart", "cart_id = :cart_id", $data, "no");
    echo $result;
    exit;
}
$title = "Proto";
require_once 'lib/siteConstant.php';
require_once 'lib/header_user.php';
require_once 'lib/navbar.php';
if (!isset($_SESSION['customer_id'])) {
    header("Location:index.php");
} else {
    $data = array(
        ":customer_id" => array(
            "value" => $_SESSION['customer_id'],
            "type" => 'INT'
        ),
    );
    //db connection
    $conn = new Connection();
    $result = $conn->select("Cart", "Cart.cart_id,Cart.product_id,name,Cart.quantity,Cart.price,image,summary", "LEFT JOIN", array('Products' => array("product_id" => "product_id")), "customer_id = :customer_id", $data);
    $totalPrice = 0;
    $product_id = [];
    $quantity = [];
    $totalAmount = [];
    foreach ($result as $value) {
        //Dynamic cart menu
        $product_id[] = $value['product_id'];
        $quantity[] = $value['quantity'];
        $totalAmount[] = $value['price'] * $value['quantity'];
        $totalPrice += $value['price'] * $value['quantity'];
        $output .= ' <div class="row gy-3 mb-4">
        <div class="col-lg-7    ">
            <div class="me-lg-5">
                <div class="d-flex">
                    <img src="' . SITE_URL . 'PHPOPS/eCommerce/assets/image/productUpload/' . $value["product_id"] . '/' . $value["image"] . '" class="border rounded me-3" style="width: 96px; height: 96px;">
                    <div class="">
                        <a href="#" class="nav-link">' . $value['name'] . '</a>
                        <p class="text-muted">' . $value['summary'] . '</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-sm-6 col-6 d-flex flex-row flex-lg-column flex-xl-row text-nowrap">
            <div class="me-5">
                ' . $value['quantity'] . '
            </div>
            <div class="">
                <text class="h6 ms-5"> <i class="fa-solid fa-indian-rupee-sign"></i> ' . $value['price'] * $value['quantity'] . '</text> <br>
                <small class="text-muted text-nowrap ms-5"> <i class="fa-solid fa-indian-rupee-sign"></i> ' . $value['price'] . ' / per item </small>
            </div>
        </div>
        <div class="col-lg col-sm-6 d-flex justify-content-sm-center justify-content-md-start justify-content-lg-center justify-content-xl-end mb-2">
            <div class="float-md-end">
            <button type="button" id="' . $value['cart_id'] . '" class="deleteCartProduct btn btn-outline-danger">Remove</button>
            </div>
        </div>
    </div>';
    }
    // onclick of order place button it will remove products from cart and add to the product page and also update the product quantity
    if (isset($_POST['orderPlace'])) {
        $conn = new Connection();
        $result = $conn->select("Cart");
        if (count($result) <= 0) {
            $orderPlaceOutput = '<div class="swal2-container1 swal2-container swal2-center"><div aria-labelledby="swal2-title" aria-describedby="swal2-html-container" class="swal2-popup swal2-modal swal2-icon-error swal2-show" tabindex="-1" role="dialog" aria-live="assertive" aria-modal="true" style="display: grid;">
            <ul class="swal2-progress-steps" style="display: none;"></ul>
            <div class="swal2-icon swal2-error swal2-icon-show" style="display: flex;"><span class="swal2-x-mark">
                    <span class="swal2-x-mark-line-left"></span>
                    <span class="swal2-x-mark-line-right"></span>
                </span>
            </div>
            <h2 class="swal2-title" id="swal2-title" style="display: block;">Add products to cart!!</h2>
        </div></div>';
        } else {
            $datadelete = array(
                ":customer_id" => array(
                    "value" => $_SESSION['customer_id'],
                    "type" => 'INT'
                ),
            );

            for ($i = 0; $i < count($product_id); $i++) {
                $dataArr = array(
                    "customer_id" => $_SESSION['customer_id'],
                    "product_id" => $product_id[$i],
                    "quantity" => $quantity[$i],
                    "order_date" => date("Y/m/d"),
                    "total_amount" => $totalAmount[$i],
                    "order_status" => 1
                );
                $dataSelectQuantity = array(
                    ":product_id" => array(
                        "value" => $product_id[$i],
                        "type" => 'INT'
                    ),
                );
                //insert in order table
                $conn->insert("Orders", $dataArr);
                //delete from cart table
                $conn->delete("Cart", "customer_id = :customer_id", $datadelete, "no");
                //update the product table
                $result = $conn->select("Products", "quantity", null, null, "product_id=:product_id", $dataSelectQuantity);
                $updatedQuantity = $result[0]['quantity'] - $quantity[$i];
                $dataArr = array(
                    "quantity" => $updatedQuantity,
                    "product_id" => $product_id[$i]
                );
                $result = $conn->update('Products', $dataArr, 'product_id = :product_id');
            }
            echo "<script>
            window.location.href = '" . SITE_URL . "PHPOPS/eCommerce/orderStatus.php?status=success';
            </script>";
        }
    }
}
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-9">
            <div class="card border shadow-0">
                <div class="m-4">
                    <h4 class="card-title mb-4">Your shopping cart</h4>
                    <?php echo $output;
                    echo $orderPlaceOutput; ?>

                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="card shadow-0 border">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <p class="mb-2">Total price:</p>
                        <p class="mb-2"><i class="fa-solid fa-indian-rupee-sign"></i> <?php echo $totalPrice ?></p>
                    </div>
                    <div class="d-flex justify-content-between">
                        <p class="mb-2">TAX:</p>
                        <p class="mb-2"><i class="fa-solid fa-indian-rupee-sign"></i> <?php echo ceil(($totalPrice * 5) / 100) ?></p>
                    </div>
                    <div class="d-flex justify-content-between">
                        <p class="mb-2">Delivery charges:</p>
                        <p class="mb-2"><i class="fa-solid fa-indian-rupee-sign"></i>0</p>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-success">Free Delivery (COD)</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <p class="mb-2">Total price:</p>
                        <p class="mb-2 fw-bold"><i class="fa-solid fa-indian-rupee-sign"></i> <?php echo $totalPrice + ceil(($totalPrice * 5) / 100) ?></p>
                    </div>
                    <div class="mt-3">
                        <form method="post">
                            <button type="submit" name="orderPlace" class="btn btn-success w-100 shadow-0 mb-2">Place Order</button>
                        </form>
                        <a href="<?php echo SITE_URL; ?>PHPOPS/eCommerce/index.php" class="btn btn-outline-secondary w-100 border mt-2"> Back to shop </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
echo $script;
require_once 'lib/footer.php';
?>