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

export async function createBoard(title: string): Promise<Board> {
    const response = await apiClient.post<Board>('/boards', { title });
    return response.data;
}

export async function updateBoard(uuid: string, title: string): Promise<Board> {
    const response = await apiClient.put<Board>(`/boards/${uuid}`, { title });
    return response.data;
}

export async function deleteBoard(uuid: string): Promise<void> {
    await apiClient.delete(`/boards/${uuid}`);
}
