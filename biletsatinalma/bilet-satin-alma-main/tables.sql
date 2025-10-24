-- User tablosu
PRAGMA foreign_keys = ON;
CREATE TABLE User (
    id TEXT PRIMARY KEY,
    full_name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    role TEXT NOT NULL CHECK(role IN ('user', 'company', 'admin')),
    password TEXT NOT NULL,
    company_id TEXT,
    balance INTEGER DEFAULT 800,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    FOREIGN KEY(company_id) REFERENCES Bus_Company(id)
);

-- Bus_Company tablosu
CREATE TABLE Bus_Company (
    id TEXT PRIMARY KEY,
    name TEXT UNIQUE NOT NULL,
    logo_path TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Trips tablosu
CREATE TABLE Trips (
    id TEXT PRIMARY KEY,
    company_id TEXT NOT NULL,
    destination_city TEXT NOT NULL,
    arrival_time DATETIME NOT NULL,
    departure_time DATETIME NOT NULL,
    departure_city TEXT NOT NULL,
    price INTEGER NOT NULL,
    capacity INTEGER NOT NULL,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES Bus_Company(id)
);

-- Tickets tablosu
CREATE TABLE Tickets (
    id TEXT PRIMARY KEY,
    trip_id TEXT NOT NULL,
    user_id TEXT NOT NULL,
    status TEXT DEFAULT 'active' CHECK(status IN ('active', 'canceled', 'expired')),
    total_price INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES Trips(id),
    FOREIGN KEY (user_id) REFERENCES User(id)
);

-- Booked_Seats tablosu
CREATE TABLE Booked_Seats (
    id TEXT PRIMARY KEY,
    ticket_id TEXT NOT NULL,
    seat_number INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES Tickets(id)
);

-- Coupons tablosu
CREATE TABLE Coupons (
    id TEXT PRIMARY KEY,
    code TEXT NOT NULL,
    discount REAL NOT NULL,
    company_id TEXT,
    usage_limit INTEGER NOT NULL,
    expire_date DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES Bus_Company(id)
);

-- User_Coupons tablosu
CREATE TABLE User_Coupons (
    id TEXT PRIMARY KEY,
    coupon_id TEXT NOT NULL,
    user_id TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES Coupons(id),
    FOREIGN KEY (user_id) REFERENCES User(id)
);

INSERT INTO Trips (company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity)
VALUES (1, 'Konya', 'İstanbul', '2025-10-20 09:00:00', '2025-10-20 15:00:00', 350, 40);
