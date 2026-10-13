export type FactoryData = {
    factory_id: number;
    factory_name: string;
    address: string;
    latitude: number;
    longitude: number;
    minimum_pickup_liter: string;
};
export type KilangShared = {
    kilang: {
        factory: FactoryData | null;
        permissions: { users: boolean; operations: boolean; finance: boolean };
    };
    flash: { success?: string; error?: string };
};
export type Pagination<T> = {
    data: T[];
    total: number;
    current_page: number;
    last_page: number;
    links: { url: string | null; label: string; active: boolean }[];
};
export type AgentProfile = {
    agen_id: number;
    address: string;
    latitude: string;
    longitude: string;
    bank_name: string;
    account_number: string;
    open_at: string;
    close_at: string;
    open_days: string[];
    stock_liter: number;
    user?: Person;
};
export type Person = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    role_id: number | null;
    is_active: boolean;
    agen?: AgentProfile | null;
    driver?: { plate_number: string; type_vehicle: string } | null;
    admin?: { factory_id: number } | null;
};
export type PickupRow = {
    pickup_id: number;
    agent_id: number;
    driver_id: number;
    date: string;
    time: string;
    status: string;
    destination: string;
    route_key: string | null;
    stop_order: number;
    driver?: Person;
    agent?: Person;
    transaction_agent?: { volume_liter: string };
};
export type Report = {
    from: string;
    to: string;
    income: number;
    purchase_cost: number;
    operating_expense: number;
    total_expense: number;
    profit: number;
    received_liter: number;
    sold_liter: number;
};
export type Inventory = {
    received_liter: number;
    sold_liter: number;
    stock_liter: number;
};
export type TransactionRow = {
    id: number;
    transaction_id: string;
    volume_liter: string;
    total_price: string;
    status: string;
    created_at: string;
    client?: Person | null;
    agent: Person;
    driver?: Person;
    pickup?: PickupRow | null;
    price: { price_per_liter: string };
};
export type FinanceRow = {
    id: number;
    type: string;
    date: string;
    amount: string;
    volume_liter: string | null;
    description: string;
};
export const roleLabels: Record<number, string> = {
    1: 'Superadmin',
    2: 'Admin',
    3: 'Manajer Finance',
    4: 'Manajer Operasional',
    5: 'Driver',
    6: 'Agent',
    7: 'Client',
};
export const liter = (value: number | string) =>
    `${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 3 }).format(Number(value))} L`;
export const rupiah = (value: number | string) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value));
export const dateLabel = (value: string) =>
    new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
export const localToday = () =>
    new Intl.DateTimeFormat('en-CA').format(new Date());
