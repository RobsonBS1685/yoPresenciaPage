import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, tap } from 'rxjs';
export interface ApiResponse { success: boolean; message?: string; user?: any; users?: any[]; messages?: any[]; csrfToken?: string; }
@Injectable({ providedIn: 'root' })
export class ApiService {
  private readonly base = 'http://localhost/yopresencia-api/api/index.php';
  private csrfToken = '';
  constructor(private http: HttpClient) {}
  request<T = ApiResponse>(action: string, body: object = {}): Observable<T> {
    const headers = this.csrfToken ? new HttpHeaders({ 'X-CSRF-Token': this.csrfToken }) : undefined;
    return this.http.post<T>(`${this.base}?action=${encodeURIComponent(action)}`, body, { withCredentials: true, headers }).pipe(tap((r: any) => { if (r?.csrfToken) this.csrfToken = r.csrfToken; }));
  }
  get<T = ApiResponse>(action: string): Observable<T> {
    return this.http.get<T>(`${this.base}?action=${encodeURIComponent(action)}`, { withCredentials: true }).pipe(tap((r: any) => { if (r?.csrfToken) this.csrfToken = r.csrfToken; }));
  }
}
