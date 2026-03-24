import apiClient from './client';
import { Board, BoardSummary } from '../types';

export async function getBoards(): Promise<BoardSummary[]> {
    const response = await apiClient.get<BoardSummary[]>('/boards');
    return response.data;
}

export async function getBoard(uuid: string): Promise<Board> {
    const response = await apiClient.get<Board>(`/boards/${uuid}`);
    return response.data;
}

export async function createBoard(title: string, uuid: string): Promise<Board> {
    const response = await apiClient.post<Board>('/boards', { title, uuid });
    return response.data;
}

export async function updateBoard(uuid: string, title: string): Promise<void> {
    await apiClient.put(`/boards/${uuid}`, { title });
}

export async function deleteBoard(uuid: string): Promise<void> {
    await apiClient.delete(`/boards/${uuid}`);
}
