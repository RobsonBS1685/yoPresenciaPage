import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { ApiService } from './api.service';
import { catchError, map, of } from 'rxjs';
export const adminGuard: CanActivateFn = () => {
  const api = inject(ApiService); const router = inject(Router);
  return api.get<any>('me').pipe(map(r => r?.user?.role === 'admin' ? true : router.createUrlTree(['/acceso'])), catchError(() => of(router.createUrlTree(['/acceso']))));
};
