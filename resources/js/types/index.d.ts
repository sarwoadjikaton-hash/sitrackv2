export interface User {
    id: number;
    username: string;
    name?: string;
    email?: string;
    role: string;
    roles?: string[];
    permissions?: string[];
    is_active: boolean;
    created_at?: string;
}

export interface Unit {
    id: number;
    unit_name: string;
    pic_name?: string;
    phone?: string;
    email?: string;
    address?: string;
    is_active: boolean;
}

export interface LetterCategory {
    id: number;
    category_name: string;
    description?: string;
    is_active: boolean;
}

export interface LetterNumberType {
    id: number;
    type_code: string;
    type_name: string;
    workbook_name: string;
    uses_security_access: boolean;
    extra_field: 'scan_result' | 'nd_pengantar';
    number_pattern: string;
    sequence_padding: number;
    default_signer_code: string;
    is_active: boolean;
    display_order: number;
    slot_count?: number;
    letter_numbers_count?: number;
    total_slots?: number;
    used_slots?: number;
    available_slots?: number;
    reserved_slots?: number;
}

export interface LetterNumberAvailabilityBatch {
    id: number;
    type_id: number;
    number_year: number;
    purpose: 'available' | 'preorder' | 'reservation';
    start_sequence: number;
    end_sequence: number;
    period_date: string;
    period_month?: string;
    letter_date?: string;
    unit_id?: number;
    unit_text?: string;
    pic_name?: string;
    status: string;
    notes?: string;
    created_by?: number;
    created_at: string;
    type?: LetterNumberType;
    unit?: Unit;
    creator?: User;
    slot_total?: number;
    slot_available?: number;
    slot_reserved?: number;
    slot_used?: number;
}

export interface LetterNumber {
    id: number;
    type_id: number;
    number_year: number;
    sequence_number: number;
    status: 'available' | 'reserved' | 'used';
    signer_code: string;
    security_access?: string;
    classification_code?: string;
    month_number?: number;
    number_text?: string;
    incoming_date?: string;
    unit_id?: number;
    processing_unit_text?: string;
    signatory?: string;
    request_type?: string;
    destination?: string;
    letter_date?: string;
    subject?: string;
    technical_officer?: string;
    scan_result?: string;
    nd_pengantar?: string;
    attachment_path?: string | null;
    pdf_content?: string | null;
    linked_letter_id?: number;
    reserved_for?: string;
    reserved_at?: string;
    used_at?: string;
    created_by?: number;
    type?: LetterNumberType;
    unit?: Unit;
    creator?: User;
}

export interface Letter {
    id: number;
    tracking_code: string;
    agenda_number?: string;
    letter_number?: string;
    letter_type: 'in' | 'out';
    letter_source: 'Manual' | 'SRIKANDI';
    process_lane: 'signature' | 'disposition';
    sender_unit?: string;
    category_id?: number;
    letter_number_type_id?: number | null;
    sender_name?: string;
    sender_phone?: string;
    recipient_unit_id?: number;
    subject: string;
    letter_date?: string;
    received_date?: string;
    priority: 'urgent' | 'high' | 'normal' | 'low';
    security_level: string;
    status: string;
    current_position: string;
    requested_actions?: string;
    notes?: string;
    attachment_path?: string;
    created_by?: number;
    created_at: string;
    updated_at: string;
    category?: LetterCategory;
    recipient_unit?: Unit;
    creator?: User;
    dispositions_count?: number;
    dispositions?: Disposition[];
    status_logs?: LetterStatusLog[];
}

export interface Disposition {
    id: number;
    letter_id: number;
    parent_disposition_id?: number;
    from_name: string;
    to_unit_id?: number;
    to_name?: string;
    instruction: string;
    due_date?: string;
    status: string;
    follow_up_note?: string;
    attachment_path?: string | null;
    is_koordinator: boolean;
    created_by?: number;
    disposition_date: string;
    created_at: string;
    to_unit?: Unit;
    creator?: User;
    children?: Disposition[];
    letter?: Letter;
}

export interface LetterStatusLog {
    id: number;
    letter_id: number;
    status: string;
    position?: string;
    note?: string;
    changed_by: string;
    changed_at: string;
}

export interface LetterRelation {
    id: number;
    source_letter_id: number;
    target_letter_id: number;
    relation_type: string;
    notes?: string;
    created_by?: number;
    created_at: string;
    source_letter?: Letter;
    target_letter?: Letter;
    creator?: User;
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    prev_page_url?: string;
    next_page_url?: string;
    links: { url?: string; label: string; active: boolean }[];
}

export interface PageProps {
    auth: {
        user?: User | null;
    };
    flash: {
        success?: string;
        error?: string;
        warning?: string;
        info?: string;
    };
    appName: string;
    [key: string]: any;
}
