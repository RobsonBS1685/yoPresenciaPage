import { Routes } from '@angular/router';
import { HomeComponent } from './pages/home/home.component';
import { InfoComponent } from './pages/info/info.component';
import { ContactComponent } from './pages/contact/contact.component';
import { AuthComponent } from './pages/auth/auth.component';
import { AdminComponent } from './pages/admin/admin.component';
import { adminGuard } from './core/auth.guard';

export const routes: Routes = [
  { path: '', component: HomeComponent },
  { path: 'sobre-mi', component: InfoComponent, data: { page: 'sobre-mi' } },
  { path: 'servicios', component: InfoComponent, data: { page: 'servicios' } },
  { path: 'valores', component: InfoComponent, data: { page: 'valores' } },
  { path: 'contacto', component: ContactComponent },
  { path: 'acceso', component: AuthComponent },
  { path: 'registro', component: AuthComponent, data: { mode: 'register' } },
  { path: 'admin', component: AdminComponent, canActivate: [adminGuard] },
  { path: '**', redirectTo: '' }
];
