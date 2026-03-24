import apiClient from './client';
import { BoardColumn } from '../types';

export async function createColumn(boardUuid: string, title: string): Promise<BoardColumn> {
    const response = await apiClient.post<BoardColumn>(`/boards/${boardUuid}/columns`, { title });
    return response.data;
}

export async function updateColumn(id: number, title: string): Promise<void> {
    await apiClient.put(`/columns/${id}`, { title });
}

export async function deleteColumn(id: number): Promise<void> {
    await apiClient.delete(`/columns/${id}`);
}

export async function reorderColumns(boardUuid: string, orderedColumnIds: number[]): Promise<void> {
    await apiClient.put('/columns/reorder', { boardId: boardUuid, orderedColumnIds });
}
