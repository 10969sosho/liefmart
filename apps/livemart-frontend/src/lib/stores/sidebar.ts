import { create } from 'zustand';

interface SidebarState {
  isOpen: boolean;
  width: number;
  toggle: () => void;
  setWidth: (width: number) => void;
  open: () => void;
  close: () => void;
}

export const useSidebarStore = create<SidebarState>((set) => ({
  isOpen: true,
  width: 250,
  toggle: () => set((state) => ({ isOpen: !state.isOpen })),
  setWidth: (width) => set({ width }),
  open: () => set({ isOpen: true }),
  close: () => set({ isOpen: false }),
}));
