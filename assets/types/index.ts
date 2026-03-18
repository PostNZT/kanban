export interface User {
    id: number;
    email: string;
}

export interface Board {
    id: string;
    title: string;
    columns: BoardColumn[];
    createdAt: string;
}

export interface BoardColumn {
    id: number;
    title: string;
    position: number;
    cards: Card[];
}

export interface Card {
    id: number;
    title: string;
    description: string | null;
    position: number;
    createdAt: string;
}

export interface BoardSummary {
    id: string;
    title: string;
    createdAt: string;
}
