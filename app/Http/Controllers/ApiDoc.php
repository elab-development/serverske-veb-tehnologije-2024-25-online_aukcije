<?php

namespace App\Http\Controllers;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Auctions API",
 *     version="1.0.0",
 *     description="REST API za aukcijsku aplikaciju. API koristi JSON za standardne odgovore, Sanctum Bearer tokene za zasticene rute i CSV za eksport aukcija."
 * )
 *
 * @OA\Server(
 *     url="/api",
 *     description="API base path"
 * )
 *
 * @OA\Tag(name="Auth", description="Registracija, login i logout")
 * @OA\Tag(name="Users", description="Podaci o trenutno ulogovanom korisniku")
 * @OA\Tag(name="Categories", description="Kategorije aukcija")
 * @OA\Tag(name="Auctions", description="Aukcije, pretraga, filteri i upravljanje")
 * @OA\Tag(name="Bids", description="Ponude za aukcije")
 * @OA\Tag(name="External Catalog", description="Javni katalozi proizvoda za referencu")
 * @OA\Tag(name="Exports", description="Eksport podataka")
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Sanctum token",
 *     description="Uneti token dobijen kroz /register ili /login. Format u Authorization headeru: Bearer {token}"
 * )
 *
 * @OA\Schema(
 *     schema="ErrorMessage",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Unauthorized")
 * )
 *
 * @OA\Schema(
 *     schema="ValidationError",
 *     type="object",
 *     example={"message":"The given data was invalid.","errors":{"email":{"The email has already been taken."}}}
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Admin User"),
 *     @OA\Property(property="email", type="string", format="email", example="admin@auctions.test"),
 *     @OA\Property(property="role", type="string", enum={"admin","seller","buyer"}, example="seller"),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="Category",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Elektronika"),
 *     @OA\Property(property="description", type="string", example="Telefoni, racunari, konzole i audio oprema."),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="Auction",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=2),
 *     @OA\Property(property="category_id", type="integer", example=1),
 *     @OA\Property(property="winner_id", type="integer", nullable=true, example=null),
 *     @OA\Property(property="title", type="string", example="PlayStation 5 konzola sa dva kontrolera"),
 *     @OA\Property(property="description", type="string", example="Konzola je ispravna, uz nju dolaze dva kontrolera i tri igre."),
 *     @OA\Property(property="starting_price", type="number", format="float", example=320.00),
 *     @OA\Property(property="current_price", type="number", format="float", nullable=true, example=400.00),
 *     @OA\Property(property="starts_at", type="string", format="date-time", example="2026-06-22 10:00:00"),
 *     @OA\Property(property="ends_at", type="string", format="date-time", example="2026-06-29 10:00:00"),
 *     @OA\Property(property="status", type="string", enum={"draft","active","finished","cancelled"}, example="active"),
 *     @OA\Property(property="seller", ref="#/components/schemas/User", nullable=true),
 *     @OA\Property(property="category", ref="#/components/schemas/Category", nullable=true),
 *     @OA\Property(property="winner", ref="#/components/schemas/User", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="Bid",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=3),
 *     @OA\Property(property="auction_id", type="integer", example=1),
 *     @OA\Property(property="amount", type="number", format="float", example=450.00),
 *     @OA\Property(property="buyer", ref="#/components/schemas/User", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Post(
 *     path="/register",
 *     tags={"Auth"},
 *     summary="Registracija korisnika",
 *     description="Kreira buyer ili seller nalog i vraca Sanctum Bearer token.",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name","email","password","role"},
 *             @OA\Property(property="name", type="string", maxLength=255, example="Petar Petrovic"),
 *             @OA\Property(property="email", type="string", format="email", example="petar@example.test"),
 *             @OA\Property(property="password", type="string", minLength=8, example="password123"),
 *             @OA\Property(property="role", type="string", enum={"buyer","seller"}, example="buyer")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="User registered",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", ref="#/components/schemas/User"),
 *             @OA\Property(property="access_token", type="string", example="1|plain-text-token"),
 *             @OA\Property(property="token_type", type="string", example="Bearer")
 *         )
 *     ),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
 * )
 *
 * @OA\Post(
 *     path="/login",
 *     tags={"Auth"},
 *     summary="Login korisnika",
 *     description="Vraca Sanctum Bearer token za ispravne kredencijale.",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email","password"},
 *             @OA\Property(property="email", type="string", format="email", example="petar@example.test"),
 *             @OA\Property(property="password", type="string", example="password123")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="User logged in",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Petar Petrovic logged in"),
 *             @OA\Property(property="data", ref="#/components/schemas/User"),
 *             @OA\Property(property="access_token", type="string", example="1|plain-text-token"),
 *             @OA\Property(property="token_type", type="string", example="Bearer")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Wrong credentials", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
 * )
 *
 * @OA\Post(
 *     path="/logout",
 *     tags={"Auth"},
 *     summary="Logout korisnika",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Logged out", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorMessage"))
 * )
 *
 * @OA\Get(
 *     path="/user",
 *     tags={"Users"},
 *     summary="Trenutno ulogovan korisnik",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Authenticated user", @OA\JsonContent(ref="#/components/schemas/User")),
 *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorMessage"))
 * )
 *
 * @OA\Get(
 *     path="/auction-external-catalog",
 *     tags={"External Catalog"},
 *     summary="Javni katalozi proizvoda",
 *     description="Vraca reference iz DummyJSON Products i Fake Store API kataloga.",
 *     @OA\Parameter(name="query", in="query", required=false, description="Pojam za DummyJSON pretragu", @OA\Schema(type="string", maxLength=100, example="iphone")),
 *     @OA\Parameter(name="limit", in="query", required=false, description="Broj stavki po izvoru", @OA\Schema(type="integer", minimum=1, maximum=10, example=2)),
 *     @OA\Response(response=200, description="External catalog data"),
 *     @OA\Response(response=503, description="External catalogs unavailable", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
 * )
 *
 * @OA\Get(
 *     path="/categories",
 *     tags={"Categories"},
 *     summary="Lista kategorija",
 *     @OA\Response(
 *         response=200,
 *         description="Categories list",
 *         @OA\JsonContent(
 *             @OA\Property(property="count", type="integer", example=5),
 *             @OA\Property(property="categories", type="array", @OA\Items(ref="#/components/schemas/Category"))
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/categories",
 *     tags={"Categories"},
 *     summary="Kreiranje kategorije",
 *     description="Kategoriju moze kreirati samo admin.",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name","description"},
 *             @OA\Property(property="name", type="string", maxLength=255, example="Satovi"),
 *             @OA\Property(property="description", type="string", example="Rucni, dzepni i kolekcionarski satovi.")
 *         )
 *     ),
 *     @OA\Response(response=201, description="Category created"),
 *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=403, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
 * )
 *
 * @OA\Get(
 *     path="/categories/{category}",
 *     tags={"Categories"},
 *     summary="Pregled jedne kategorije",
 *     @OA\Parameter(name="category", in="path", required=true, description="Category ID", @OA\Schema(type="integer", example=1)),
 *     @OA\Response(response=200, description="Category details"),
 *     @OA\Response(response=404, description="Category not found", @OA\JsonContent(ref="#/components/schemas/ErrorMessage"))
 * )
 *
 * @OA\Put(
 *     path="/categories/{category}",
 *     tags={"Categories"},
 *     summary="Azuriranje kategorije",
 *     description="Kategoriju moze azurirati samo admin.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="category", in="path", required=true, description="Category ID", @OA\Schema(type="integer", example=1)),
 *     @OA\RequestBody(
 *         required=false,
 *         @OA\JsonContent(
 *             @OA\Property(property="name", type="string", example="Kolekcionarski satovi"),
 *             @OA\Property(property="description", type="string", example="Satovi za kolekcionare.")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Category updated"),
 *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=403, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=404, description="Category not found", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
 * )
 *
 * @OA\Patch(
 *     path="/categories/{category}",
 *     tags={"Categories"},
 *     summary="Delimicno azuriranje kategorije",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="category", in="path", required=true, description="Category ID", @OA\Schema(type="integer", example=1)),
 *     @OA\RequestBody(required=false, @OA\JsonContent(@OA\Property(property="name", type="string", example="Satovi"))),
 *     @OA\Response(response=200, description="Category updated"),
 *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=403, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=404, description="Category not found", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
 * )
 *
 * @OA\Delete(
 *     path="/categories/{category}",
 *     tags={"Categories"},
 *     summary="Brisanje kategorije",
 *     description="Kategoriju moze obrisati samo admin.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="category", in="path", required=true, description="Category ID", @OA\Schema(type="integer", example=1)),
 *     @OA\Response(response=200, description="Category deleted", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=403, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=404, description="Category not found", @OA\JsonContent(ref="#/components/schemas/ErrorMessage"))
 * )
 *
 * @OA\Get(
 *     path="/categories/{category}/auctions",
 *     tags={"Categories","Auctions"},
 *     summary="Aukcije jedne kategorije",
 *     @OA\Parameter(name="category", in="path", required=true, description="Category ID", @OA\Schema(type="integer", example=1)),
 *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string", example="PlayStation")),
 *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"draft","active","finished","cancelled"})),
 *     @OA\Parameter(name="seller_id", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="winner_id", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="min_price", in="query", required=false, @OA\Schema(type="number", format="float")),
 *     @OA\Parameter(name="max_price", in="query", required=false, @OA\Schema(type="number", format="float")),
 *     @OA\Parameter(name="sort_by", in="query", required=false, @OA\Schema(type="string", enum={"title","status","starting_price","current_price","starts_at","ends_at","created_at","updated_at"})),
 *     @OA\Parameter(name="sort_direction", in="query", required=false, @OA\Schema(type="string", enum={"asc","desc"})),
 *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", minimum=1, maximum=50)),
 *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", minimum=1)),
 *     @OA\Response(response=200, description="Category auctions"),
 *     @OA\Response(response=404, description="Category not found", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
 * )
 *
 * @OA\Get(
 *     path="/auctions",
 *     tags={"Auctions"},
 *     summary="Lista aukcija",
 *     description="Javna ruta sa pretragom, filterima, sortiranjem i paginacijom.",
 *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string", example="PlayStation")),
 *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"draft","active","finished","cancelled"})),
 *     @OA\Parameter(name="category_id", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="seller_id", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="user_id", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="winner_id", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="min_price", in="query", required=false, @OA\Schema(type="number", format="float")),
 *     @OA\Parameter(name="max_price", in="query", required=false, @OA\Schema(type="number", format="float")),
 *     @OA\Parameter(name="starts_from", in="query", required=false, @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="starts_until", in="query", required=false, @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="ends_from", in="query", required=false, @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="ends_until", in="query", required=false, @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="sort_by", in="query", required=false, @OA\Schema(type="string", enum={"title","status","starting_price","current_price","starts_at","ends_at","created_at","updated_at"})),
 *     @OA\Parameter(name="sort_direction", in="query", required=false, @OA\Schema(type="string", enum={"asc","desc"})),
 *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", minimum=1, maximum=50)),
 *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", minimum=1)),
 *     @OA\Response(response=200, description="Paginated auctions"),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
 * )
 *
 * @OA\Post(
 *     path="/auctions",
 *     tags={"Auctions"},
 *     summary="Kreiranje aukcije",
 *     description="Aukciju moze kreirati samo seller.",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"category_id","title","description","starting_price","starts_at","ends_at"},
 *             @OA\Property(property="category_id", type="integer", example=1),
 *             @OA\Property(property="title", type="string", example="PlayStation 5 konzola"),
 *             @OA\Property(property="description", type="string", example="Konzola sa dva kontrolera."),
 *             @OA\Property(property="starting_price", type="number", format="float", example=300),
 *             @OA\Property(property="starts_at", type="string", format="date-time", example="2026-06-22 10:00:00"),
 *             @OA\Property(property="ends_at", type="string", format="date-time", example="2026-06-29 10:00:00"),
 *             @OA\Property(property="status", type="string", enum={"draft","active"}, example="draft")
 *         )
 *     ),
 *     @OA\Response(response=201, description="Auction created"),
 *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=403, description="Only sellers can create auctions", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
 * )
 *
 * @OA\Get(
 *     path="/auctions/export",
 *     tags={"Exports","Auctions"},
 *     summary="CSV eksport aukcija",
 *     description="Preuzima CSV fajl sa aukcijama, kategorijom, prodavcem, pobednikom i brojem bidova.",
 *     @OA\Response(
 *         response=200,
 *         description="CSV file",
 *         @OA\MediaType(
 *             mediaType="text/csv",
 *             @OA\Schema(type="string", example="id,title,description,category,seller,seller_email,winner,winner_email,starting_price,current_price,status,starts_at,ends_at,bids_count,created_at,updated_at")
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/auctions/{auction}",
 *     tags={"Auctions"},
 *     summary="Pregled jedne aukcije",
 *     @OA\Parameter(name="auction", in="path", required=true, description="Auction ID", @OA\Schema(type="integer", example=1)),
 *     @OA\Response(response=200, description="Auction details"),
 *     @OA\Response(response=404, description="Auction not found", @OA\JsonContent(ref="#/components/schemas/ErrorMessage"))
 * )
 *
 * @OA\Put(
 *     path="/auctions/{auction}",
 *     tags={"Auctions"},
 *     summary="Azuriranje aukcije",
 *     description="Aukciju moze azurirati njen seller ili admin. Dostupna polja zavise od statusa aukcije.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="auction", in="path", required=true, description="Auction ID", @OA\Schema(type="integer", example=1)),
 *     @OA\RequestBody(
 *         required=false,
 *         @OA\JsonContent(
 *             @OA\Property(property="category_id", type="integer", example=1),
 *             @OA\Property(property="title", type="string", example="Azurirana aukcija"),
 *             @OA\Property(property="description", type="string", example="Azuriran opis aukcije."),
 *             @OA\Property(property="starting_price", type="number", format="float", example=350),
 *             @OA\Property(property="starts_at", type="string", format="date-time", example="2026-06-22 10:00:00"),
 *             @OA\Property(property="ends_at", type="string", format="date-time", example="2026-06-30 10:00:00"),
 *             @OA\Property(property="status", type="string", enum={"draft","active","finished","cancelled"}, example="active")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Auction updated"),
 *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=403, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=404, description="Auction not found", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
 * )
 *
 * @OA\Patch(
 *     path="/auctions/{auction}",
 *     tags={"Auctions"},
 *     summary="Delimicno azuriranje aukcije",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="auction", in="path", required=true, description="Auction ID", @OA\Schema(type="integer", example=1)),
 *     @OA\RequestBody(required=false, @OA\JsonContent(@OA\Property(property="description", type="string", example="Novi opis."))),
 *     @OA\Response(response=200, description="Auction updated"),
 *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=403, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=404, description="Auction not found", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
 * )
 *
 * @OA\Delete(
 *     path="/auctions/{auction}",
 *     tags={"Auctions"},
 *     summary="Brisanje aukcije",
 *     description="Aukciju moze obrisati njen seller ili admin, ali samo ako je status draft ili cancelled.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="auction", in="path", required=true, description="Auction ID", @OA\Schema(type="integer", example=1)),
 *     @OA\Response(response=200, description="Auction deleted", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=403, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=404, description="Auction not found", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=409, description="Auction cannot be deleted in current status", @OA\JsonContent(ref="#/components/schemas/ErrorMessage"))
 * )
 *
 * @OA\Post(
 *     path="/bids",
 *     tags={"Bids"},
 *     summary="Kreiranje ili azuriranje bida",
 *     description="Buyer salje aukciju i iznos. Ako vec ima bid na toj aukciji, iznos se azurira ako je veci.",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"auction_id","amount"},
 *             @OA\Property(property="auction_id", type="integer", example=1),
 *             @OA\Property(property="amount", type="number", format="float", example=450.00)
 *         )
 *     ),
 *     @OA\Response(response=201, description="Bid created"),
 *     @OA\Response(response=200, description="Bid updated"),
 *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=403, description="Only buyers can bid", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=409, description="Auction does not accept bids", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
 * )
 *
 * @OA\Get(
 *     path="/auctions/{auction}/bids",
 *     tags={"Bids"},
 *     summary="Pregled bidova za aukciju",
 *     description="Buyer vidi svoj bid za aukciju. Seller vidi sve bidove samo za svoju aukciju. Admin vidi bidove za svaku aukciju.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="auction", in="path", required=true, description="Auction ID", @OA\Schema(type="integer", example=1)),
 *     @OA\Response(response=200, description="Bids list or buyer bid"),
 *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=403, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorMessage")),
 *     @OA\Response(response=404, description="Auction not found", @OA\JsonContent(ref="#/components/schemas/ErrorMessage"))
 * )
 */
class ApiDoc extends Controller
{
}
