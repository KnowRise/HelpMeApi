<?php

use App\Http\Controllers\API\AuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return
    '<h1 align="center"> KnowRise </h1> <br>
    <div style="display: flex; justify-content: center;">
        <div class="tenor-gif-embed" data-postid="1489386840712152603" data-share-method="host" data-aspect-ratio="0.939759" data-width="50%">
            <a href="https://tenor.com/view/yippee-happy-yippee-creature-yippee-meme-yippee-gif-gif-1489386840712152603">Yippee Happy GIF</a> from
            <a href="https://tenor.com/search/yippee-gifs">Yippee GIFs</a>
        </div>
        <script type="text/javascript" async src="https://tenor.com/embed.js"></script>
    </div>



    <!-- Row 1 with a gap in the side -->
    <p align="center">
        <!-- Empty space to create the gap -->
        <span style="display:inline-block; width: 200px;"></span>
        <img alt="Image1" title="Image1" src="https://wallpapers.com/images/hd/aesthetic-single-banana-ca70e4qdhc1z45gy.jpg" width="200">
        <!-- Empty space to create the gap -->
        <span style="display:inline-block; width: 200px;"></span>
    </p>

    <!-- Row 2 with a gap in the middle -->
    <p align="center">
        <img alt="image2" title="image2" src="https://c.stocksy.com/a/JIeA00/z9/2538175.jpg" width="150">
        <img alt="Image2" title="Image2" src="https://th.bing.com/th/id/OIP.n4eE549GEDLSzK--Te_TTAHaEK?rs=1&pid=ImgDetMain" width="200">
        <!-- Empty space to create the gap -->
        <span style="display:inline-block; width: 200px;"></span>
        <img alt="Image2" title="Image2" src="https://th.bing.com/th/id/OIP.n4eE549GEDLSzK--Te_TTAHaEK?rs=1&pid=ImgDetMain" width="200">
        <img alt="image2" title="image2" src="https://c.stocksy.com/a/JIeA00/z9/2538175.jpg" width="150">
    </p>

    <p align="center">
        <img alt="Image1" title="Image1" src="https://c.stocksy.com/a/JIeA00/z9/2538175.jpg" width="200"> <!-- Ganti src dengan URL gambar yang diinginkan -->
        <!-- Empty space to create the first gap -->
        <span style="display:inline-block; width: 200px;"></span>
        <!-- Empty space to create the second gap -->
        <span style="display:inline-block; width: 200px;"></span>
        <img alt="Image2" title="Image2" src="https://c.stocksy.com/a/JIeA00/z9/2538175.jpg" width="200"> <!-- Ganti src dengan URL gambar yang diinginkan -->
    </p>

    <!-- Row 2 with a gap in the middle -->
    <p align="center">
        <img alt="image2" title="image2" src="https://c.stocksy.com/a/JIeA00/z9/2538175.jpg" width="50" height="150">
        <img alt="image3" title="image3" src="https://th.bing.com/th/id/OIP.n4eE549GEDLSzK--Te_TTAHaEK?rs=1&pid=ImgDetMain" width="150"> <!-- Ganti src dengan URL gambar yang diinginkan -->
        <img alt="image3" title="image3" src="https://th.bing.com/th/id/OIP.n4eE549GEDLSzK--Te_TTAHaEK?rs=1&pid=ImgDetMain" width="100"> <!-- Ganti src dengan URL gambar yang diinginkan -->
        <img alt="image5" title="image5" src="https://th.bing.com/th/id/OIP.n4eE549GEDLSzK--Te_TTAHaEK?rs=1&pid=ImgDetMain" width="50"> <!-- Ganti src dengan URL gambar yang diinginkan -->
        <img alt="image6" title="image6" src="https://th.bing.com/th/id/OIP.n4eE549GEDLSzK--Te_TTAHaEK?rs=1&pid=ImgDetMain" width="100"> <!-- Ganti src dengan URL gambar yang diinginkan -->
        <img alt="image3" title="image3" src="https://th.bing.com/th/id/OIP.n4eE549GEDLSzK--Te_TTAHaEK?rs=1&pid=ImgDetMain" width="150"> <!-- Ganti src dengan URL gambar yang diinginkan -->
        <img alt="image2" title="image2" src="https://c.stocksy.com/a/JIeA00/z9/2538175.jpg" width="50" height="150">
    </p>

    <!-- Row 3 with all images -->
    <!-- <p align="center">
        <img alt="Image1" title="Image1" src="https://www.hdwallpapers.in/thumbs/2020/single_yellow_banana_in_green_background_hd_banana-t2.jpg" width="200">
        <img alt="Image1" title="Image1" src="https://www.hdwallpapers.in/thumbs/2020/single_yellow_banana_in_green_background_hd_banana-t2.jpg" width="200">
        <img alt="Image1" title="Image1" src="https://www.hdwallpapers.in/thumbs/2020/single_yellow_banana_in_green_background_hd_banana-t2.jpg" width="200">
    </p> -->';
});
//     return
//         '<h1 style="text-align:center">Transaksi kamu tidak selesai</h1>
//     <h1 align="center"> KnowRise </h1> <br>

// <!-- Row 1 with a gap in the side -->
// <p align="center">
//     <!-- Empty space to create the gap -->
//     <span style="display:inline-block; width: 200px;"></span>
//     <img alt="Image1" title="Image1" src="https://wallpapers.com/images/hd/aesthetic-single-banana-ca70e4qdhc1z45gy.jpg" width="200">
//     <!-- Empty space to create the gap -->
//     <span style="display:inline-block; width: 200px;"></span>
// </p>

// <!-- Row 2 with a gap in the middle -->
// <p align="center">
//     <img alt="image2" title="image2" src="https://c.stocksy.com/a/JIeA00/z9/2538175.jpg" width="150">
//     <img alt="Image2" title="Image2" src="https://th.bing.com/th/id/OIP.n4eE549GEDLSzK--Te_TTAHaEK?rs=1&pid=ImgDetMain" width="200">
//     <!-- Empty space to create the gap -->
//     <span style="display:inline-block; width: 200px;"></span>
//     <img alt="Image2" title="Image2" src="https://th.bing.com/th/id/OIP.n4eE549GEDLSzK--Te_TTAHaEK?rs=1&pid=ImgDetMain" width="200">
//     <img alt="image2" title="image2" src="https://c.stocksy.com/a/JIeA00/z9/2538175.jpg" width="150">
// </p>

// <p align="center">
//     <img alt="Image1" title="Image1" src="https://c.stocksy.com/a/JIeA00/z9/2538175.jpg" width="200"> <!-- Ganti src dengan URL gambar yang diinginkan -->
//     <!-- Empty space to create the first gap -->
//     <span style="display:inline-block; width: 200px;"></span>
//     <!-- Empty space to create the second gap -->
//     <span style="display:inline-block; width: 200px;"></span>
//     <img alt="Image2" title="Image2" src="https://c.stocksy.com/a/JIeA00/z9/2538175.jpg" width="200"> <!-- Ganti src dengan URL gambar yang diinginkan -->
// </p>

// <!-- Row 2 with a gap in the middle -->
// <p align="center">
//     <img alt="image2" title="image2" src="https://c.stocksy.com/a/JIeA00/z9/2538175.jpg" width="50" height="150">
//     <img alt="image3" title="image3" src="https://th.bing.com/th/id/OIP.n4eE549GEDLSzK--Te_TTAHaEK?rs=1&pid=ImgDetMain" width="150"> <!-- Ganti src dengan URL gambar yang diinginkan -->
//     <img alt="image3" title="image3" src="https://th.bing.com/th/id/OIP.n4eE549GEDLSzK--Te_TTAHaEK?rs=1&pid=ImgDetMain" width="100"> <!-- Ganti src dengan URL gambar yang diinginkan -->
//     <img alt="image5" title="image5" src="https://th.bing.com/th/id/OIP.n4eE549GEDLSzK--Te_TTAHaEK?rs=1&pid=ImgDetMain" width="50"> <!-- Ganti src dengan URL gambar yang diinginkan -->
//     <img alt="image6" title="image6" src="https://th.bing.com/th/id/OIP.n4eE549GEDLSzK--Te_TTAHaEK?rs=1&pid=ImgDetMain" width="100"> <!-- Ganti src dengan URL gambar yang diinginkan -->
//     <img alt="image3" title="image3" src="https://th.bing.com/th/id/OIP.n4eE549GEDLSzK--Te_TTAHaEK?rs=1&pid=ImgDetMain" width="150"> <!-- Ganti src dengan URL gambar yang diinginkan -->
//     <img alt="image2" title="image2" src="https://c.stocksy.com/a/JIeA00/z9/2538175.jpg" width="50" height="150">
// </p>

// <!-- Row 3 with all images -->
// <!-- <p align="center">
//     <img alt="Image1" title="Image1" src="https://www.hdwallpapers.in/thumbs/2020/single_yellow_banana_in_green_background_hd_banana-t2.jpg" width="200">
//     <img alt="Image1" title="Image1" src="https://www.hdwallpapers.in/thumbs/2020/single_yellow_banana_in_green_background_hd_banana-t2.jpg" width="200">
//     <img alt="Image1" title="Image1" src="https://www.hdwallpapers.in/thumbs/2020/single_yellow_banana_in_green_background_hd_banana-t2.jpg" width="200">
// </p> -->
// ';
// });

// Route::get('/error', function () {
//     return
//         '<h1 style="text-align:center">Transaksi kamu Error</h1>
//     <h1 align="center"> KnowRise </h1> <br>

// <!-- Row 1 with a gap in the side -->
// <p align="center">
//     <!-- Empty space to create the gap -->
//     <span style="display:inline-block; width: 200px;"></span>
//     <img alt="Image1" title="Image1" src="https://wallpapers.com/images/hd/aesthetic-single-banana-ca70e4qdhc1z45gy.jpg" width="200">
//     <!-- Empty space to create the gap -->
//     <span style="display:inline-block; width: 200px;"></span>
// </p>

// <!-- Row 2 with a gap in the middle -->
// <p align="center">
//     <img alt="image2" title="image2" src="https://c.stocksy.com/a/JIeA00/z9/2538175.jpg" width="150">
//     <img alt="Image2" title="Image2" src="https://th.bing.com/th/id/OIP.n4eE549GEDLSzK--Te_TTAHaEK?rs=1&pid=ImgDetMain" width="200">
//     <!-- Empty space to create the gap -->
//     <span style="display:inline-block; width: 200px;"></span>
//     <img alt="Image2" title="Image2" src="https://th.bing.com/th/id/OIP.n4eE549GEDLSzK--Te_TTAHaEK?rs=1&pid=ImgDetMain" width="200">
//     <img alt="image2" title="image2" src="https://c.stocksy.com/a/JIeA00/z9/2538175.jpg" width="150">
// </p>

// <p align="center">
//     <img alt="Image1" title="Image1" src="https://c.stocksy.com/a/JIeA00/z9/2538175.jpg" width="200"> <!-- Ganti src dengan URL gambar yang diinginkan -->
//     <!-- Empty space to create the first gap -->
//     <span style="display:inline-block; width: 200px;"></span>
//     <!-- Empty space to create the second gap -->
//     <span style="display:inline-block; width: 200px;"></span>
//     <img alt="Image2" title="Image2" src="https://c.stocksy.com/a/JIeA00/z9/2538175.jpg" width="200"> <!-- Ganti src dengan URL gambar yang diinginkan -->
// </p>

// <!-- Row 2 with a gap in the middle -->
// <p align="center">
//     <img alt="image2" title="image2" src="https://c.stocksy.com/a/JIeA00/z9/2538175.jpg" width="50" height="150">
//     <img alt="image3" title="image3" src="https://th.bing.com/th/id/OIP.n4eE549GEDLSzK--Te_TTAHaEK?rs=1&pid=ImgDetMain" width="150"> <!-- Ganti src dengan URL gambar yang diinginkan -->
//     <img alt="image3" title="image3" src="https://th.bing.com/th/id/OIP.n4eE549GEDLSzK--Te_TTAHaEK?rs=1&pid=ImgDetMain" width="100"> <!-- Ganti src dengan URL gambar yang diinginkan -->
//     <img alt="image5" title="image5" src="https://th.bing.com/th/id/OIP.n4eE549GEDLSzK--Te_TTAHaEK?rs=1&pid=ImgDetMain" width="50"> <!-- Ganti src dengan URL gambar yang diinginkan -->
//     <img alt="image6" title="image6" src="https://th.bing.com/th/id/OIP.n4eE549GEDLSzK--Te_TTAHaEK?rs=1&pid=ImgDetMain" width="100"> <!-- Ganti src dengan URL gambar yang diinginkan -->
//     <img alt="image3" title="image3" src="https://th.bing.com/th/id/OIP.n4eE549GEDLSzK--Te_TTAHaEK?rs=1&pid=ImgDetMain" width="150"> <!-- Ganti src dengan URL gambar yang diinginkan -->
//     <img alt="image2" title="image2" src="https://c.stocksy.com/a/JIeA00/z9/2538175.jpg" width="50" height="150">
// </p>

// <!-- Row 3 with all images -->
// <!-- <p align="center">
//     <img alt="Image1" title="Image1" src="https://www.hdwallpapers.in/thumbs/2020/single_yellow_banana_in_green_background_hd_banana-t2.jpg" width="200">
//     <img alt="Image1" title="Image1" src="https://www.hdwallpapers.in/thumbs/2020/single_yellow_banana_in_green_background_hd_banana-t2.jpg" width="200">
//     <img alt="Image1" title="Image1" src="https://www.hdwallpapers.in/thumbs/2020/single_yellow_banana_in_green_background_hd_banana-t2.jpg" width="200">
// </p> -->
// ';
// });

require __DIR__ . '/auth.php';
