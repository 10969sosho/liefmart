import * as React from "react"
import { cva, type VariantProps } from "class-variance-authority"

import { cn } from "@/lib/utils"

const badgeVariants = cva(
  "inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold transition-all duration-200 ring-1 ring-inset",
  {
    variants: {
      variant: {
        default:
          "bg-blue-50 text-blue-700 ring-blue-600/20 hover:bg-blue-100",
        success:
          "bg-green-50 text-green-700 ring-green-600/20 hover:bg-green-100",
        warning:
          "bg-amber-50 text-amber-700 ring-amber-600/20 hover:bg-amber-100",
        danger:
          "bg-red-50 text-red-700 ring-red-600/20 hover:bg-red-100",
        info:
          "bg-cyan-50 text-cyan-700 ring-cyan-600/20 hover:bg-cyan-100",
        purple:
          "bg-purple-50 text-purple-700 ring-purple-600/20 hover:bg-purple-100",
        gray:
          "bg-gray-50 text-gray-700 ring-gray-600/20 hover:bg-gray-100",
        outline:
          "bg-transparent text-gray-700 ring-gray-300 hover:bg-gray-50",
      },
      size: {
        default: "px-3 py-1 text-xs",
        sm: "px-2 py-0.5 text-[10px]",
        lg: "px-4 py-1.5 text-sm",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  }
)

export interface BadgeProps
  extends React.HTMLAttributes<HTMLDivElement>,
    VariantProps<typeof badgeVariants> {}

function Badge({ className, variant, size, ...props }: BadgeProps) {
  return (
    <div className={cn(badgeVariants({ variant, size }), className)} {...props} />
  )
}

export { Badge, badgeVariants }
