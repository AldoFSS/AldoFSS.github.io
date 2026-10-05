<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Lightbox CSS -->
    <link rel="stylesheet"href="node_modules/lightbox2/dist/css/lightbox.min.css";>
  <!----------------------------------------------------------------------------------------------->
  
  <style>
  /* Make the image fully responsive */
  .carousel-inner img {
    width: 100%;
    height: 700px;
    
  }
  .carousel-caption 
  {
    top: 50%;
  }
  .carousel-caption h3
  {
    font-size: 40px;
  }
  </style>

</head>
<body>
  <header>
    <?php include("includes/navbar.php");?>
  </header>
  <!------------------------------------------------------------------->
  <div id="carouselExample" class="carousel slide">
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#carouselExample" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
        <button type="button" data-bs-target="#carouselExample" data-bs-slide-to="1" aria-label="Slide 2"></button>
        <button type="button" data-bs-target="#carouselExample" data-bs-slide-to="2" aria-label="Slide 3"></button>
    </div>
    <div class="carousel-inner">
        <div class="carousel-item active">
            <img src="IMG/f1.webp" class="d-block w-100 img-responsive" loading="lazy" style="height: 500px; max-width: 500px object-fit: cover;" alt="Slide 1">
            <div class="carousel-caption d-flex align-items-center justify-content-center">
                <div class="carousel-text">
                    <h1>CAFELIZ</h1>
                    <p>Que su experiencia en este sitio sea agradable</p>
                </div>
            </div>
        </div>
        <div class="carousel-item">
            <img src="IMG/f5.webp" class="d-block w-100 img-responsive" loading="lazy" style="height: 500px; object-fit: cover;" alt="Slide 2">
            <div class="carousel-caption d-flex align-items-center justify-content-center">
                <div class="carousel-text">
                    <h2>BIENVENIDO</h2>
                    <p>Desayuno, Bebidas, Antojitos, Postres y más. </p>
                    
                    <p>Un sitio donde encontrarás una variedad para tu paladar.</p>
                </div>
            </div>
        </div>
        <div class="carousel-item">
            <img src="IMG/f2.webp" class="d-block w-100 img-responsive" loading="lazy" style="height: 500px; object-fit: cover;" alt="Slide 3">
            <div class="carousel-caption d-flex align-items-center justify-content-center">
                <div class="carousel-text">
                    <h2>CAFELIZ</h2>
                    <p>El día comienza después de una buena taza de café.</p>
                </div>
            </div>
        </div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#carouselExample" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#carouselExample" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>

<style>
    /* Hacer el carousel completamente responsivo */
    .carousel-inner {
        width: 100%;
        max-height: 500px;
        overflow: hidden;
        position: relative;
    }

    /* Asegurar que las imágenes no se deformen */
    .carousel-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Centramos el texto con flexbox */
    .carousel-caption {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
        color: white;
        text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.5);
    }

    /* Ajustamos el tamaño del texto */
    .carousel-text h1, .carousel-text h2, .carousel-text p {
        margin: 0;
        padding: 0;
    }

    .carousel-text h1, .carousel-text h2 {
        font-size: 4.5vw; /* Ajuste dinámico para el tamaño del texto */
        max-width: 90%; /* No dejar que el texto se desborde */
        overflow-wrap: break-word; /* Romper palabras largas */
    }

    .carousel-text p {
        overflow-wrap: break-word; /* Romper palabras largas */
    }

    /* Media Queries para mejorar la visibilidad en pantallas más pequeñas */
    @media (max-width: 768px) {
        .carousel-text h1, .carousel-text h2 {
            font-size: 6vw; /* Texto más pequeño en pantallas móviles */
        }
        .carousel-text p {
            font-size: 4vw; /* Ajuste de texto en pantallas más pequeñas */
        }
    }

    @media (max-width: 576px) {
        .carousel-text h1, .carousel-text h2 {
            font-size: 8vw; /* Texto aún más pequeño en pantallas pequeñas */
        }
    }
</style>
  <!------------------------------------------------------------------->
  <div class="photo-gallery">
    <div class="container">
      <div class="intro">
        <h3 class="text-center">Galeria</h3>
        <p class="text-center text-black">Nunc luctus in metus eget fringilla. Aliquam sed justo ligula. Vestibulum nibh erat, pellentesque ut laoreet vitae.</p>
      </div>
      <div class="row photos">
        <div class="col-sm-6 col-md-4 col-lg-3 item"><a href="IMG/blog-img-01.webp" data-lightbox="photos" alt="..."><img class="img-fluid" style=" width: 100%; height: 100%;" loading="lazy" src="IMG/blog-img-01.webp" alt="..."></a></div>
        <div class="col-sm-6 col-md-4 col-lg-3 item"><a href="IMG/blog-img-02.webp" data-lightbox="photos" alt="..."><img class="img-fluid" style=" width: 100%; height: 100%;" loading="lazy" src="IMG/blog-img-02.webp" alt="..."></a></div>
        <div class="col-sm-6 col-md-4 col-lg-3 item"><a href="IMG/blog-img-03.webp" data-lightbox="photos" alt="..."><img class="img-fluid" style=" width: 100%; height: 100%;" loading="lazy" src="IMG/blog-img-03.webp" alt="..."></a></div>
        <div class="col-sm-6 col-md-4 col-lg-3 item"><a href="IMG/blog-img-04.webp" data-lightbox="photos" alt="..."><img class="img-fluid" style=" width: 100%; height: 100%;" loading="lazy" src="IMG/blog-img-04.webp" alt="..."></a></div>
        <div class="col-sm-6 col-md-4 col-lg-3 item"><a href="IMG/blog-img-05.webp" data-lightbox="photos" alt="..."><img class="img-fluid" style=" width: 100%; height: 100%;" loading="lazy" src="IMG/blog-img-05.webp" alt="..."></a></div>
        <div class="col-sm-6 col-md-4 col-lg-3 item"><a href="IMG/blog-img-06.webp" data-lightbox="photos" alt="..."><img class="img-fluid" style=" width: 100%; height: 100%;" loading="lazy" src="IMG/blog-img-06.webp" alt="..."></a></div>
        <div class="col-sm-6 col-md-4 col-lg-3 item"><a href="IMG/blog-img-07.webp" data-lightbox="photos" alt="..."><img class="img-fluid" style=" width: 100%; height: 100%;" loading="lazy" src="IMG/blog-img-07.webp" alt="..."></a></div>
        <div class="col-sm-6 col-md-4 col-lg-3 item"><a href="IMG/blog-img-08.webp" data-lightbox="photos" alt="..."><img class="img-fluid" style=" width: 100%; height: 100%;" loading="lazy" src="IMG/blog-img-08.webp" alt="..."></a></div>
      </div>
    </div>
  </div>

  <?php include("includes/footer.html");?>
  <script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="node_modules/lightbox2/dist/js/lightbox.min.js"></script>


</body>
</html>
