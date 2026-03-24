import apiClient from './client';
import { Card } from '../types';

export async function createCard(
    columnId: number,
    title: string,
    description?: string
): Promise<Card> {
    const response = await apiClient.post<Card>(`/columns/${columnId}/cards`, {
        title,
        description,
    });
    return response.data;
}

export async function updateCard(
    id: number,
    data: { title?: string; description?: string | null }
): Promise<void> {
    await apiClient.put(`/cards/${id}`, data);
}

export async function deleteCard(id: number): Promise<void> {
    await apiClient.delete(`/cards/${id}`);
}

export async function moveCard(
    cardId: number,
    targetColumnId: number,
    targetPosition: number
): Promise<void> {
    await apiClient.put('/cards/move', { cardId, targetColumnId, targetPosition });
}
