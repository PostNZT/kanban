import apiClient from './client';
import { User } from '../types';

interface LoginResponse {
    user: User;
}

interface RegisterResponse {
    id: number;
    email: string;
}

export async function login(email: string, password: string): Promise<LoginResponse> {
    const response = await apiClient.post<LoginResponse>('/login', { email, password });
    return response.data;
}

export async function register(email: string, password: string): Promise<RegisterResponse> {
    const response = await apiClient.post<RegisterResponse>('/register', { email, password });
    return response.data;
}

export async function logout(): Promise<void> {
    await apiClient.post('/logout');
}
